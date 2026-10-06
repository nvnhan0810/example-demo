locals {
  master_ip  = "192.168.1.232" # Thay bằng IP VM 1
  replica_ip = "192.168.1.231" # Thay bằng IP VM 2
  repl_user  = "dbUser"
  repl_pass  = "dbPassword@123"
}

# ==========================================
# 1. CẤU HÌNH MASTER (VM 1)
# ==========================================
resource "null_resource" "config_master" {
  connection {
    type        = "ssh"
    host        = local.master_ip
    user        = var.proxmox_system_user_username
    private_key = file(var.ssh_private_key_path)
  }

  provisioner "remote-exec" {
    inline = [
      "echo '=== ĐANG CẤU HÌNH MASTER ==='",

      # Dùng \n để ngắt dòng thay vì Enter trực tiếp trong code Terraform
      "sudo bash -c 'cat <<EOF > /etc/mysql/mysql.conf.d/99-replication.cnf\n[mysqld]\nserver-id = 1\ngtid_mode = ON\nenforce_gtid_consistency = ON\nEOF'",

      # Restart để nhận cấu hình
      "sudo systemctl restart mysql",

      # Tạo user đồng bộ dữ liệu
      "sudo mysql -e \"CREATE USER IF NOT EXISTS '${local.repl_user}'@'%' IDENTIFIED BY '${local.repl_pass}';\"",
      "sudo mysql -e \"GRANT REPLICATION SLAVE ON *.* TO '${local.repl_user}'@'%';\"",
      "sudo mysql -e \"FLUSH PRIVILEGES;\"",

      "echo '=== MASTER CẤU HÌNH XONG ==='"
    ]
  }
}

# ==========================================
# 2. CẤU HÌNH REPLICA (VM 2)
# ==========================================
resource "null_resource" "config_replica" {
  depends_on = [null_resource.config_master]

  connection {
    type        = "ssh"
    host        = local.replica_ip
    user        = var.proxmox_system_user_username
    private_key = file(var.ssh_private_key_path)
  }

  provisioner "remote-exec" {
    inline = [
      "echo '=== ĐANG CẤU HÌNH REPLICA ==='",

      # Dùng \n để ngắt dòng
      "sudo bash -c 'cat <<EOF > /etc/mysql/mysql.conf.d/99-replication.cnf\n[mysqld]\nserver-id = 2\ngtid_mode = ON\nenforce_gtid_consistency = ON\nread_only = 1\nEOF'",

      # Restart để nhận cấu hình
      "sudo systemctl restart mysql",

      # Reset trạng thái cũ (nếu có) và thiết lập kết nối tới Master bằng GTID
      "sudo mysql -e \"STOP REPLICA;\"",
      "sudo mysql -e \"RESET REPLICA ALL;\"",
      "sudo mysql -e \"CHANGE REPLICATION SOURCE TO SOURCE_HOST='${local.master_ip}', SOURCE_USER='${local.repl_user}', SOURCE_PASSWORD='${local.repl_pass}', SOURCE_AUTO_POSITION=1;\"",
      "sudo mysql -e \"START REPLICA;\"",

      "echo '=== REPLICA CẤU HÌNH XONG ==='"
    ]
  }
}
