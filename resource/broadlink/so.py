import os
import shutil
from setuptools import setup, Extension
from Cython.Build import cythonize

# Chạy lệnh: python3 so.py build_ext --inplace

# Danh sách module có đuôi .py
module_names = [
    "Broadlink_VBot.py"
]

# Đổi tên file .py thành .pyx nếu chưa đổi
for mod in module_names:
    if mod.endswith('.py'):
        py_file = mod
        pyx_file = mod[:-3] + '.pyx'  # Đổi đuôi .py -> .pyx
    else:
        py_file = mod + '.py'
        pyx_file = mod + '.pyx'

    if os.path.exists(py_file) and not os.path.exists(pyx_file):
        print(f"Đổi tên {py_file} --> {pyx_file}")
        shutil.copy2(py_file, pyx_file)

extensions = [
    Extension(
        mod[:-3] if mod.endswith('.py') else mod,        # Tên module không đuôi .py
        [mod[:-3] + '.pyx' if mod.endswith('.py') else mod + '.pyx'],  # File .pyx tương ứng
        extra_compile_args=["-O3"],     # Bật tối ưu hóa cao nhất C
        #extra_link_args=[],             # Có thể thêm tùy chọn linker ở đây nếu cần
    )
    for mod in module_names
]

setup(
    ext_modules=cythonize(
        extensions,
        compiler_directives={
            "language_level": "3",  # Chỉ thị sử dụng Python 3
            "boundscheck": False,   # Tắt kiểm tra giới hạn mảng
            #"wraparound": False,    # Tắt hỗ trợ chỉ số âm
            "cdivision": True       # Dùng phép chia C
        },
        #annotate=True  # Tạo file HTML phân tích tối ưu hóa
    )
)
