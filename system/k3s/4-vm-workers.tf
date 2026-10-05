module "vm-worker-1" {

  source = "../modules/vm-basic"

  vm_name    = "k3s-worker-1"
  cpu_cores  = 2
  memory_mb  = 2048
  disk_size  = 20
  ip_address = "192.168.1.241"

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
  inventory_host = "k3s-worker-1"
}

module "vm-worker-2" {

  source = "../modules/vm-basic"

  vm_name    = "k3s-worker-2"
  cpu_cores  = 2
  memory_mb  = 2048
  disk_size  = 20
  ip_address = "192.168.1.242"

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
  inventory_host = "k3s-woker-2"
}
