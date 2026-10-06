#!/bin/bash

# 1. Khai báo đường dẫn tuyệt đối đến file kubeconfig custom của bạn
CUSTOM_KUBECONFIG="./k3s.yaml"

# (Ví dụ với K3s mặc định, đường dẫn thường là: /etc/rancher/k3s/k3s.yaml)

# 2. Thực thi kubectl với sudo, gắn flag --kubeconfig và truyền toàn bộ tham số ("$@")
sudo kubectl --kubeconfig="$CUSTOM_KUBECONFIG" "$@"