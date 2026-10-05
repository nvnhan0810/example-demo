variable "proxmox_host" {}
variable "proxmox_api_token" {}
variable "proxmox_insecure" {}
variable "proxmox_ubuntu_template_vm_id" {}
variable "proxmox_system_user_username" {}
variable "proxmox_system_user_password" {}
variable "proxmox_system_user_ssh_keys" {}
variable "ssh_private_key_path" {}
variable "proxmox_default_dns_servers" {}

# Ansible Configuration
variable "inventory_file" {
  description = "Ansible inventory file path"
  type        = string
  default     = "~/www/system/ansible/inventory_prod.yml"
}

variable "inventory_host" {
  description = "Ansible inventory host name"
  type        = string
  default     = "haproxy"
}

variable "worker_nodes" {
  default = {
    production-worker-node-1 = {
      name         = "k3s-worker-1"
      ip_address   = "192.168.1.241"
      ansible_host = "k3s-worker-1"
    }
    production-worker-node-2 = {
      name         = "k3s-worker-2"
      ip_address   = "192.168.1.242"
      ansible_host = "k3s-worker-2"
    }
  }
}
