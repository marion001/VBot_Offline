# DLNA Audio Renderer for VBot

VBot builds gmrender-resurrect v0.3.1 from the maintained fork at `https://github.com/marion001/gmrender-resurrect`. The source is kept in that Git repository, so it does not need to be included in the VBot release tree. The renderer and its GDBus MPRIS interface run in one C program; VBot reads and controls it through the system D-Bus.

## Build and install on Raspberry Pi OS / Debian

From the VBot project directory, run:

```sh
sudo sh resource/dlna_renderer/install.sh
```

The installer clones the `master` branch into a temporary directory, checks that the repository contains the VBot MPRIS and Next/Previous integration, builds it, installs the binary, and removes the temporary source tree. Push the updated fork before running the installer on a device.

If the VBot installer itself was copied from Windows and reports `not found`, normalize its line endings, then rerun:

```sh
sed -i 's/\r$//' resource/dlna_renderer/install.sh resource/dlna_renderer/run.sh
sudo sh resource/dlna_renderer/install.sh
```

The installer installs the compiler, development libraries, and GStreamer base/good audio plugins, builds the cloned source, installs `/usr/local/bin/gmediarender`, then registers the VBot service. In the WebUI, enable **DLNA Audio Renderer** and restart VBot. The renderer sends audio to the ALSA `vbot_aec_media` sink so it shares VBot's media audio path.

The renderer advertises `VBot {full_name}` from `contact_info.full_name` in `Config.json` (or `VBot` if the name is blank).

## D-Bus interface

The renderer owns `org.mpris.MediaPlayer2.gmediarender` on the system bus at `/org/mpris/MediaPlayer2`.
The installer adds a D-Bus policy allowing the existing `pi` service account to own this name and VBot to call its methods.

Standard interface `org.mpris.MediaPlayer2.Player` provides playback status, title, artist, album, artwork URI (when supplied by the DLNA sender), duration, current position, volume, and Play/Pause/PlayPause/Stop/Seek/SetPosition. Next uses the queued URI supplied by the DLNA controller with `SetNextAVTransportURI`. Previous returns to the last track the renderer advanced from. The renderer advertises `CanGoNext` and `CanGoPrevious` according to their availability; Next is unavailable if the sender has not queued another URI. `OpenUri` is also supported.

VBot mute is exposed as `com.vbot.MediaRenderer1.Muted` and `SetMute(b)` because MPRIS has no standard mute property.
The UPnP RenderingControl volume level (0–100) is exposed as `com.vbot.MediaRenderer1.VolumeLevel` and set with `SetVolumeLevel(i)`. VBot uses this value so its displayed percentage follows the DLNA sender's level; the standard MPRIS `Volume` remains a linear audio-gain value and uses a different scale.
The custom `SenderAddress` property reports the IP address of the UPnP control point that set the current stream. VBot resolves this address to a local hostname when reverse DNS is available; otherwise the WebUI shows the IP address as the sender identifier. UPnP does not require controllers to expose a friendly device name in `SetAVTransportURI` requests.

Examples to inspect and control the renderer:

```sh
busctl --system get-property org.mpris.MediaPlayer2.gmediarender /org/mpris/MediaPlayer2 org.mpris.MediaPlayer2.Player PlaybackStatus
busctl --system get-property org.mpris.MediaPlayer2.gmediarender /org/mpris/MediaPlayer2 org.mpris.MediaPlayer2.Player Metadata
busctl --system call org.mpris.MediaPlayer2.gmediarender /org/mpris/MediaPlayer2 org.mpris.MediaPlayer2.Player Play
busctl --system call org.mpris.MediaPlayer2.gmediarender /org/mpris/MediaPlayer2 org.mpris.MediaPlayer2.Player Pause
busctl --system call org.mpris.MediaPlayer2.gmediarender /org/mpris/MediaPlayer2 org.mpris.MediaPlayer2.Player Stop
busctl --system call org.mpris.MediaPlayer2.gmediarender /org/mpris/MediaPlayer2 org.mpris.MediaPlayer2.Player Next
busctl --system call org.mpris.MediaPlayer2.gmediarender /org/mpris/MediaPlayer2 org.mpris.MediaPlayer2.Player Previous
busctl --system get-property org.mpris.MediaPlayer2.gmediarender /org/mpris/MediaPlayer2 com.vbot.MediaRenderer1 VolumeLevel
busctl --system call org.mpris.MediaPlayer2.gmediarender /org/mpris/MediaPlayer2 com.vbot.MediaRenderer1 SetVolumeLevel i 50
busctl --system call org.mpris.MediaPlayer2.gmediarender /org/mpris/MediaPlayer2 org.mpris.MediaPlayer2.Player Seek x 5000000
busctl --system call org.mpris.MediaPlayer2.gmediarender /org/mpris/MediaPlayer2 org.mpris.MediaPlayer2.Player OpenUri s https://example.com/audio.mp3
busctl --system call org.mpris.MediaPlayer2.gmediarender /org/mpris/MediaPlayer2 com.vbot.MediaRenderer1 SetMute b true
busctl --system call org.mpris.MediaPlayer2.gmediarender /org/mpris/MediaPlayer2 org.freedesktop.DBus.Properties Set ssv org.mpris.MediaPlayer2.Player Volume d 0.5
```

VBot receives `PropertiesChanged` events for metadata, playback state, volume, and mute. It samples the local D-Bus `Position` property once per second while playing so the WebUI and API can show progress; it does not repeatedly query the UPnP SOAP endpoints.

Check service logs with:

```sh
systemctl status vbot-dlna-renderer.service
journalctl -u vbot-dlna-renderer.service -f
```
