variable "vm_name" {
  description = "Name of the master VM"
  type        = string
}

variable "cpu_cores" {
  description = "Number of CPU cores"
  type        = number
  default     = 2
}

variable "memory_mb" {
  description = "Memory in MB"
  type        = number
  default     = 4096
}

variable "disk_size" {
  description = "Disk size in GB"
  type        = number
  default     = 20
}

variable "ip_address" {
  description = "IP address for the master node"
  type        = string
}

variable "proxmox_ubuntu_template_vm_id" {
  description = "Proxmox Ubuntu template VM ID"
  type        = number
}

variable "proxmox_system_user_username" {
  description = "Proxmox system user username"
  type        = string
}

variable "proxmox_system_user_password" {
  description = "Proxmox system user password"
  type        = string
}

variable "proxmox_system_user_ssh_keys" {
  description = "Proxmox system user SSH keys"
  type        = list(string)
}

variable "proxmox_default_dns_servers" {
  description = "Default DNS servers"
  type        = list(string)
}

variable "ssh_private_key_path" {
  description = "Path to SSH private key"
  type        = string
}

variable "inventory_file" {
  description = "Ansible inventory file"
  type        = string
}

variable "inventory_host" {
  description = "Ansible inventory host"
  type        = string
}
