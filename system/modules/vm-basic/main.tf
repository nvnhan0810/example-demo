terraform {
  required_providers {
    proxmox = {
      source  = "bpg/proxmox"
      version = "0.78.2"
    }
    null = {
      source  = "hashicorp/null"
      version = "~> 3.0"
    }
  }
}

# Master Node Module
# This module creates and configures a master node VM

resource "proxmox_virtual_environment_vm" "vm_basic" {
  name      = var.vm_name
  node_name = "proxmox"

  cpu {
    cores = var.cpu_cores
    type  = "x86-64-v2-AES"
  }

  memory {
    dedicated = var.memory_mb
  }

  clone {
    vm_id     = var.proxmox_ubuntu_template_vm_id
    node_name = "proxmox"
    full      = true
  }

  agent {
    enabled = true
  }
  stop_on_destroy = true

  initialization {
    user_account {
      username = var.proxmox_system_user_username
      password = var.proxmox_system_user_password
      keys     = var.proxmox_system_user_ssh_keys
    }

    dns {
      servers = var.proxmox_default_dns_servers
    }

    ip_config {
      ipv4 {
        address = "${var.ip_address}/24"
        gateway = "192.168.1.1"
      }
    }
  }

  disk {
    datastore_id = "local-lvm"
    size         = var.disk_size
    interface    = "scsi0"
    discard      = "on"
  }
}

# Wait for master VM to be ready
resource "null_resource" "wait_for_vm" {
  depends_on = [proxmox_virtual_environment_vm.vm_basic]

  connection {
    type        = "ssh"
    host        = proxmox_virtual_environment_vm.vm_basic.ipv4_addresses[1][0]
    user        = var.proxmox_system_user_username
    private_key = file(var.ssh_private_key_path)
    timeout     = "5m"
  }

  provisioner "remote-exec" {
    inline = [
      "echo 'VM is ready'",
      "hostname",
      "ip addr show"
    ]
  }
}

# Configure SSH keys for master node
module "ssh_keys" {
  source = "../ssh-keys"

  inventory_file = var.inventory_file
  inventory_host = var.inventory_host

  depends_on = [null_resource.wait_for_vm]
}
