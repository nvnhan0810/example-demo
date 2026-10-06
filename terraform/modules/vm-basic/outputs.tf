output "vm_ip_address" {
  description = "IP address of the VM"
  value       = proxmox_virtual_environment_vm.vm_basic.ipv4_addresses[1][0]
}

output "vm_name" {
  description = "Name of the VM"
  value       = proxmox_virtual_environment_vm.vm_basic.name
}

output "vm_id" {
  description = "ID of the VM"
  value       = proxmox_virtual_environment_vm.vm_basic.vm_id
}
