const { spawn } = require('child_process');
const fs = require('fs');
const path = require('path');

const PORT = process.env.PORT || 4000;

// Tìm đường dẫn thực thi PHP
function findPhpBinary() {
  const commonPaths = [
    'C:\\xampp\\php\\php.exe',
    'C:\\laragon\\bin\\php\\php-8.2.12-Win32-vs16-x64\\php.exe',
    'C:\\tools\\php\\php.exe',
    'C:\\php\\php.exe'
  ];
  for (const p of commonPaths) {
    if (fs.existsSync(p)) return p;
  }
  return 'php';
}

const phpBin = findPhpBinary();
const routerPath = path.join(__dirname, 'router.php');

console.log(`\n======================================================`);
console.log(`🚀 Khởi động PHP Server: Tuyển sinh lớp 10 Cần Thơ`);
console.log(`🔧 PHP Binary:  ${phpBin}`);
console.log(`🌐 Local URL:   http://127.0.0.1:${PORT}`);
console.log(`📄 Trang chủ:   http://127.0.0.1:${PORT}/`);
console.log(`🔍 Tra cứu:     http://127.0.0.1:${PORT}/ket-qua`);
console.log(`🔐 Đăng nhập:   http://127.0.0.1:${PORT}/auth/login`);
console.log(`======================================================\n`);

const phpProcess = spawn(phpBin, ['-S', `127.0.0.1:${PORT}`, routerPath], {
  cwd: __dirname,
  stdio: 'inherit'
});

phpProcess.on('error', (err) => {
  console.error('Không thể khởi động PHP server:', err.message);
  console.log('Bạn có thể chạy trực tiếp lệnh:');
  console.log(`  ${phpBin} -S 127.0.0.1:${PORT} router.php`);
});

phpProcess.on('exit', (code) => {
  console.log(`PHP server đã dừng (code: ${code})`);
});

