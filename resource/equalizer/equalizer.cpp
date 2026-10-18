// Six stereo biquads. No allocations or locks in the processing callback.
#include <cmath>
#include <cstdint>
#include <new>
#include <algorithm>
#include <initializer_list>
namespace {
constexpr double pi=3.14159265358979323846;
struct Filter {
    double b0=1,b1=0,b2=0,a1=0,a2=0,z1[2]={},z2[2]={};
    double run(double x,int channel) {
        double y=b0*x+z1[channel];
        z1[channel]=b1*x-a1*y+z2[channel];z2[channel]=b2*x-a2*y;
        // Avoid subnormal tails consuming CPU on ARM after audio goes silent.
        if(std::abs(z1[channel])<1e-24)z1[channel]=0;
        if(std::abs(z2[channel])<1e-24)z2[channel]=0;
        return y;
    }
};
struct EQ {Filter filters[6];double gain,preamp,wet;};
Filter coefficients(int kind,double frequency,double db,int rate) {
    Filter f;
    if(db==0)return f;
    const double A=std::pow(10.,db/40),w=2*pi*frequency/rate;
    const double c=std::cos(w),s=std::sin(w);
    double b0,b1,b2,a0,a1,a2;
    if(kind==1) {
        const double alpha=s/(2*.7071067811865476);
        b0=1+alpha*A;b1=-2*c;b2=1-alpha*A;
        a0=1+alpha/A;a1=-2*c;a2=1-alpha/A;
    } else {
        const double alpha=s/std::sqrt(2.),t=2*std::sqrt(A)*alpha;
        if(kind==0) {
            b0=A*((A+1)-(A-1)*c+t);b1=2*A*((A-1)-(A+1)*c);b2=A*((A+1)-(A-1)*c-t);
            a0=(A+1)+(A-1)*c+t;a1=-2*((A-1)+(A+1)*c);a2=(A+1)+(A-1)*c-t;
        } else {
            b0=A*((A+1)+(A-1)*c+t);b1=-2*A*((A-1)+(A+1)*c);b2=A*((A+1)+(A-1)*c-t);
            a0=(A+1)-(A-1)*c+t;a1=2*((A-1)-(A+1)*c);a2=(A+1)-(A-1)*c-t;
        }
    }
    f.b0=b0/a0;f.b1=b1/a0;f.b2=b2/a0;f.a1=a1/a0;f.a2=a2/a0;return f;
}
}
extern "C" {
int vbot_eq_band_count(){return 6;}
void* vbot_eq_create6(int rate,double bass,double low_mid,double mid,double high_mid,double treble,double air,double preamp,double wet) {
    if(rate!=44100 && rate!=48000)return nullptr;
    for(double value:{bass,low_mid,mid,high_mid,treble,air})if(!std::isfinite(value)||std::abs(value)>12)return nullptr;
    if(!std::isfinite(preamp)||preamp>0||preamp< -12||!std::isfinite(wet)||wet<0||wet>100)return nullptr;
    EQ* eq=new(std::nothrow) EQ;
    if(!eq)return nullptr;
    double values[]={bass,low_mid,mid,high_mid,treble,air},freq[]={100,250,630,1600,4000,10000};
    double headroom=0;
    for(int i=0;i<6;i++){eq->filters[i]=coefficients(i==0?0:(i==5?2:1),freq[i],values[i],rate);headroom+=std::max(0.,values[i]);}
    eq->gain=std::pow(10.,-headroom/20.);eq->preamp=std::pow(10.,preamp/20.);eq->wet=wet/100.;
    return eq;
}
int vbot_eq_process(void* handle,int16_t* pcm,int frames) {
    if(!handle||!pcm||frames<0)return -1;
    auto& eq=*static_cast<EQ*>(handle);
    if(eq.wet==0 && eq.preamp==1)return 0;
    for(int i=0;i<frames;i++)for(int ch=0;ch<2;ch++) {
        double dry=pcm[2*i+ch],x=dry*eq.gain;
        for(auto& filter:eq.filters)x=filter.run(x,ch);
        x=(dry*(1-eq.wet)+x*eq.wet)*eq.preamp;
        pcm[2*i+ch]=static_cast<int16_t>(std::lrint(std::max(-32768.,std::min(32767.,x))));
    }
    return 0;
}
void vbot_eq_destroy(void* handle){delete static_cast<EQ*>(handle);}
int vbot_eq_mix(const int16_t* old_pcm,int16_t* next_pcm,int frames,int progressed,int total) {
    if(!old_pcm||!next_pcm||frames<0||progressed<0||total<=0)return -1;
    for(int i=0;i<frames;i++) {
        double weight=std::min(1.,double(progressed+i+1)/total);
        for(int ch=0;ch<2;ch++) {
            int index=i*2+ch;
            next_pcm[index]=static_cast<int16_t>(std::lrint(old_pcm[index]*(1-weight)+next_pcm[index]*weight));
        }
    }
    return 0;
}
}
