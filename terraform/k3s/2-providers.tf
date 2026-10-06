terraform {
  required_version = ">= 1.0"

  required_providers {
    proxmox = {
      source  = "bpg/proxmox"
      version = "0.78.2"
    }
    null = {
      source  = "hashicorp/null"
      version = "~> 3.0"
    }
    kubernetes = {
      source  = "hashicorp/kubernetes"
      version = ">= 2.25.2"
    }
    helm = {
      source  = "hashicorp/helm"
      version = ">= 2.12.1"
    }
    time = {
      source  = "hashicorp/time"
      version = "~> 0.9.0"
    }
  }
}

provider "proxmox" {
  endpoint  = var.proxmox_host
  api_token = var.proxmox_api_token
  insecure  = var.proxmox_insecure
}
