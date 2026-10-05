# SSH Keys Configuration Module
# This module copies SSH keys and creates SSH config for any project

resource "null_resource" "check_inventory" {
  provisioner "local-exec" {
    command = "ANSIBLE_HOST_KEY_CHECKING=False ansible all -i ${var.inventory_file} -m ping --limit ${var.inventory_host}"
  }
}

resource "null_resource" "configure_ssh_keys" {
  provisioner "local-exec" {
    command = "cd ${path.module}/../../ansible/ssh-github-playbook && ANSIBLE_HOST_KEY_CHECKING=False  ansible-playbook -i ${var.inventory_file} --limit ${var.inventory_host} add-github-ssh.yml"
  }

  depends_on = [null_resource.check_inventory]
}
