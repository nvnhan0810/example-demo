module "db-replica" {

  source = "../modules/vm-basic"

  vm_name    = "db-replica"
  cpu_cores  = 2
  memory_mb  = 4096
  disk_size  = 30
  ip_address = "192.168.1.231"

  # Proxmox configuration
  proxmox_ubuntu_template_vm_id = var.proxmox_ubuntu_template_vm_id
  proxmox_system_user_username  = var.proxmox_system_user_username
  proxmox_system_user_password  = var.proxmox_system_user_password
  proxmox_system_user_ssh_keys  = var.proxmox_system_user_ssh_keys
  proxmox_default_dns_servers   = var.proxmox_default_dns_servers

  # SSH configuration
  ssh_private_key_path = var.ssh_private_key_path

  # Ansible configuration
  inventory_file = var.inventory_file
  inventory_host = "db-replica"
}
