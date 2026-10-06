locals {
  db_ips = ["192.168.1.232", "192.168.1.231"]
}

resource "null_resource" "install_mysql8" {
  # Triggers giúp bạn quản lý việc chạy lại script. 
  # Ví dụ: đổi ID này script sẽ chạy lại, hoặc dùng always_run = timestamp() để chạy mỗi lần gõ lệnh apply
  triggers = {
    setup_version = "1.0"
  }

  for_each = toset(local.db_ips)

  # 1. Cấu hình SSH để Terraform kết nối vào VM đã có
  connection {
    type        = "ssh"
    host        = each.value                       # IP của VM Proxmox hiện tại của bạn
    user        = var.proxmox_system_user_username # User SSH (vd: ubuntu, root, debian)
    private_key = file(var.ssh_private_key_path)   # Đường dẫn khóa SSH hoặc dùng password = "mật_khẩu"
    # password  = "MatKhauSSH"          # Bỏ comment dòng này (và xóa private_key) nếu bạn dùng mật khẩu
  }

  # 2. Thực thi các lệnh cài đặt MySQL 8.x qua SSH
  provisioner "remote-exec" {
    inline = [
      # Cập nhật repo và cài MySQL Server (Trên Ubuntu 20.04/22.04 mặc định là bản 8.x)
      "sudo apt-get update -y",
      "sudo apt-get install -y mysql-server",

      # Thay đổi cấu hình bind-address cho phép mọi IP (0.0.0.0) kết nối vào
      # Cú pháp sed này an toàn hơn, thay thế bất kỳ dòng nào bắt đầu bằng bind-address
      "sudo sed -i 's/^bind-address\\s*=.*/bind-address = 0.0.0.0/' /etc/mysql/mysql.conf.d/mysqld.cnf",

      # (Tuỳ chọn) Mở thêm bind-address cho MySQLX plugin của bản 8.x nếu cần dùng port 33060
      "sudo sed -i 's/^mysqlx-bind-address\\s*=.*/mysqlx-bind-address = 0.0.0.0/' /etc/mysql/mysql.conf.d/mysqld.cnf || true",

      # Khởi động lại dịch vụ MySQL
      "sudo systemctl restart mysql",

      # TẠO USER VÀ CẤP QUYỀN (Lưu ý: MySQL 8.x bắt buộc phải tách riêng lệnh CREATE và GRANT)
      "sudo mysql -e \"CREATE USER IF NOT EXISTS 'dbUser'@'%' IDENTIFIED BY 'dbPassword@123';\"",
      "sudo mysql -e \"GRANT ALL PRIVILEGES ON *.* TO 'dbUser'@'%';\"",
      "sudo mysql -e \"FLUSH PRIVILEGES;\"",

      # Đảm bảo tường lửa UFW (nếu có trên OS) mở port 3306
      "sudo ufw allow 3306/tcp || true",
      "echo 'Cài đặt và cấu hình MySQL 8.x hoàn tất!'"
    ]
  }
}
