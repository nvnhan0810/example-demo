############################################
# K3s Installation                         #
############################################

terraform {
  required_version = ">= 1.0"
}

# Install K3s server without Traefik and ServiceLB
resource "null_resource" "k3s_install" {
  depends_on = [module.vm-master-node-1]

  provisioner "remote-exec" {
    inline = [
      # Update system
      "sudo apt-get update -y",

      # Install K3s server with disabled components
      "curl -sfL https://get.k3s.io | sh -",

      # Wait for K3s to be ready
      "sudo k3s kubectl wait --for=condition=Ready nodes --all --timeout=300s",

      # Verify K3s installation
      "sudo k3s kubectl get nodes",
      "sudo k3s kubectl get pods -A",
    ]

    connection {
      type        = "ssh"
      host        = "192.168.1.240"
      user        = var.proxmox_system_user_username
      private_key = file(var.ssh_private_key_path)
    }
  }
}
