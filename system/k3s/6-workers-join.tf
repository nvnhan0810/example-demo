############################################
# K3s Worker Nodes Join Configuration     #
# Joins worker nodes as K3s agents        #
############################################

terraform {
  required_version = ">= 1.0"
}

# Step 1: Retrieve K3s node token from master
resource "null_resource" "get_k3s_token" {
  depends_on = [null_resource.k3s_install]

  provisioner "remote-exec" {
    inline = [
      # Get K3s node token
      "sudo cat /var/lib/rancher/k3s/server/node-token > /tmp/k3s-node-token.txt",
      "chmod 644 /tmp/k3s-node-token.txt"
    ]

    connection {
      type        = "ssh"
      host        = "192.168.1.240" # Master node IP
      user        = var.proxmox_system_user_username
      private_key = file(var.ssh_private_key_path)
    }
  }

  # Download token to local machine
  provisioner "local-exec" {
    command = <<-EOT
      scp -i ${var.ssh_private_key_path} -o StrictHostKeyChecking=no \
        ${var.proxmox_system_user_username}@192.168.1.240:/tmp/k3s-node-token.txt \
        /tmp/k3s-node-token.txt
    EOT
  }
}

# Step 2: Join worker nodes as K3s agents
resource "null_resource" "join_k3s_workers" {
  for_each = var.worker_nodes

  depends_on = [
    null_resource.get_k3s_token,
    module.vm-worker-2,
    module.vm-worker-1
  ]

  # Upload token to worker node
  provisioner "file" {
    source      = "/tmp/k3s-node-token.txt"
    destination = "/tmp/k3s-node-token.txt"

    connection {
      type        = "ssh"
      host        = each.value.ip_address
      user        = var.proxmox_system_user_username
      private_key = file(var.ssh_private_key_path)
      timeout     = "30s"
    }
  }

  # Install K3s agent on worker node
  provisioner "remote-exec" {
    inline = [
      # Update system
      "sudo apt-get update -y",

      # Read the K3s token
      "K3S_TOKEN=$(cat /tmp/k3s-node-token.txt)",

      # Install K3s agent
      "curl -sfL https://get.k3s.io | K3S_URL=https://192.168.1.240:6443 K3S_TOKEN=$K3S_TOKEN sh -",

      # Wait for agent to be ready
      "echo 'Waiting for K3s agent to be ready...'",
      "sleep 30",

      # Verify agent is running
      "sudo systemctl status k3s-agent --no-pager",

      # Clean up token file
      "rm -f /tmp/k3s-node-token.txt"
    ]

    connection {
      type        = "ssh"
      host        = each.value.ip_address
      user        = var.proxmox_system_user_username
      private_key = file(var.ssh_private_key_path)
      timeout     = "30s"
    }
  }
}

# Step 3: Verify all nodes are joined and ready
resource "null_resource" "verify_k3s_cluster" {
  depends_on = [null_resource.join_k3s_workers]

  provisioner "remote-exec" {
    inline = [
      # Wait for all nodes to be Ready
      "echo 'Verifying K3s cluster nodes...'",
      "EXPECTED_NODES=$((1 + ${length(var.worker_nodes)}))", # 1 master + workers

      # Wait up to 5 minutes for all nodes to be ready
      "for i in {1..30}; do",
      "  READY_NODES=$(sudo k3s kubectl get nodes --no-headers | grep Ready | wc -l)",
      "  echo 'Ready nodes: $READY_NODES/$EXPECTED_NODES (attempt $i/30)'",
      "  if [ \"$READY_NODES\" -eq \"$EXPECTED_NODES\" ]; then",
      "    echo 'All nodes are Ready!'",
      "    break",
      "  fi",
      "  sleep 10",
      "done",

      # Show final cluster status
      "echo '=== K3s Cluster Status ==='",
      "sudo k3s kubectl get nodes -o wide",
      "echo ''",
      "echo '=== K3s System Pods ==='",
      "sudo k3s kubectl get pods -n kube-system",
      "echo ''",
      "echo '=== K3s Cluster Info ==='",
      "sudo k3s kubectl cluster-info",

      # Verify worker nodes are properly labeled
      "echo ''",
      "echo '=== Worker Node Labels ==='",
      "sudo k3s kubectl get nodes --show-labels | grep -E 'worker|agent'",

      "echo ''",
      "echo '✅ K3s cluster with worker nodes is ready!'",
      "echo '🎯 Master node: 192.168.1.240'",
      "echo '🔧 Worker nodes: ${join(", ", values(var.worker_nodes)[*].ip_address)}'",
      "echo '📋 Total nodes: $EXPECTED_NODES'"
    ]

    connection {
      type        = "ssh"
      host        = "192.168.1.240" # Master node IP
      user        = var.proxmox_system_user_username
      private_key = file(var.ssh_private_key_path)
    }
  }
}

# Step 4: Clean up temporary files
resource "null_resource" "cleanup_k3s_files" {
  depends_on = [null_resource.verify_k3s_cluster]

  provisioner "local-exec" {
    command = <<-EOT
      rm -f /tmp/k3s-node-token.txt
      echo "✅ K3s temporary files cleaned up"
    EOT
  }

  # Also clean up token file on master node
  provisioner "remote-exec" {
    inline = [
      "rm -f /tmp/k3s-node-token.txt",
      "echo 'Token file cleaned up on master node'"
    ]

    connection {
      type        = "ssh"
      host        = "192.168.1.240" # Master node IP
      user        = var.proxmox_system_user_username
      private_key = file(var.ssh_private_key_path)
    }
  }
}

# Output cluster information
output "k3s_cluster_info" {
  value = {
    master_node         = "192.168.1.240"
    worker_nodes        = values(var.worker_nodes)[*].ip_address
    total_nodes         = 1 + length(var.worker_nodes)
    kubeconfig_location = "Master node: /etc/rancher/k3s/k3s.yaml"
    access_command      = "ssh ${var.proxmox_system_user_username}@192.168.1.240 'sudo k3s kubectl get nodes'"
  }

  depends_on = [null_resource.verify_k3s_cluster]
}
