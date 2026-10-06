./k3s.sh apply -f https://raw.githubusercontent.com/kubernetes/kube-state-metrics/v2.10.1/examples/standard/serviceaccount.yaml
./k3s.sh apply -f https://raw.githubusercontent.com/kubernetes/kube-state-metrics/v2.10.1/examples/standard/clusterrole.yaml
./k3s.sh apply -f https://raw.githubusercontent.com/kubernetes/kube-state-metrics/v2.10.1/examples/standard/clusterrolebinding.yaml
./k3s.sh apply -f https://raw.githubusercontent.com/kubernetes/kube-state-metrics/v2.10.1/examples/standard/service.yaml
./k3s.sh apply -f https://raw.githubusercontent.com/kubernetes/kube-state-metrics/v2.10.1/examples/standard/deployment.yaml