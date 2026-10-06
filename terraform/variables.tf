# Proxmox Connection Variables
variable "proxmox_host" {
  description = "Proxmox host URL"
  type        = string
  default     = "https://your-proxmox-host:8006/api2/json"
}

variable "proxmox_username" {
  description = "Proxmox username"
  type        = string
  default     = "terraform@pam"
}

variable "proxmox_password" {
  description = "Proxmox password"
  type        = string
  default     = "123456"
}

variable "proxmox_node" {
  description = "Proxmox node"
  type        = string
  default     = "pve"
}

variable "proxmox_api_token" {
  description = "Proxmox API token"
  type        = string
  default     = "12345-123242"
  sensitive   = true
}

variable "proxmox_insecure" {
  description = "Proxmox insecure"
  type        = bool
  default     = true
}

variable "proxmox_ubuntu_template_vm_id" {
  description = "Proxmox Ubuntu Template VM ID"
  type        = number
  default     = 109
}

variable "proxmox_default_dns_servers" {
  description = "Proxmox Default DNS Servers"
  type        = list(string)
  default     = ["192.168.1.207", "8.8.8.8"]
}

variable "proxmox_system_user_username" {
  description = "Proxmox System User Username"
  type        = string
  default     = "thk-system"
}

variable "proxmox_system_user_password" {
  description = "Proxmox System User Password"
  type        = string
  default     = ""
  sensitive   = true
}

variable "proxmox_system_user_ssh_keys" {
  description = "SSH public keys for the system user"
  type        = list(string)
  default     = []
  sensitive   = true
}


# SSH Configuration
variable "ssh_private_key_path" {
  description = "Path to SSH private key for VM access"
  type        = string
  default     = "~/.ssh/id_ed25519"
}

# Consul Configuration
variable "consul_version" {
  description = "Consul version to install"
  type        = string
  default     = "1.17.0"
}

variable "consul_datacenter" {
  description = "Consul datacenter name"
  type        = string
  default     = "dc1"
}
