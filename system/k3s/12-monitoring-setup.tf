# k3s/run_ansible.tf

resource "null_resource" "run_ansible_playbook" {
  # Tính năng 'triggers' giúp Terraform nhận biết khi nào file Ansible bị thay đổi
  # Nếu bạn sửa file setup.yml hoặc inventory, Terraform apply lần sau sẽ tự động chạy lại Ansible
  triggers = {
    playbook_hash  = filesha256("${path.module}/../ansible/monitoring.yml")
    inventory_hash = filesha256("${path.module}/../ansible/ssh-github-playbook/inventory.yml")
  }

  provisioner "local-exec" {
    command = "cd ${path.module}/../ansible && ANSIBLE_HOST_KEY_CHECKING=False  ansible-playbook -i ssh-github-playbook/inventory.yml monitoring.yml"
    # Thiết lập biến môi trường
    environment = {
      # Bỏ qua bước hỏi "Are you sure you want to continue connecting (yes/no)?" khi SSH lần đầu
      ANSIBLE_HOST_KEY_CHECKING = "False"
    }
  }
}
