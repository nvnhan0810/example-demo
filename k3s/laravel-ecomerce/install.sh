#!/usr/bin/env bash
# Deploy platform (index + wallets + flc) into namespace laravel-ecomerce.
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
NAMESPACE="laravel-ecomerce"

cd "${SCRIPT_DIR}/.."

./k3s.sh apply -f "${SCRIPT_DIR}/namespace.yaml"

if ! ./k3s.sh get secret ghcr-pull-secret -n "${NAMESPACE}" &>/dev/null; then
  echo "ERROR: Secret ghcr-pull-secret not found in namespace ${NAMESPACE}."
  echo "Create it once (see README.md), then re-run install.sh"
  exit 1
fi

seal_one() {
  local env_file="$1"
  local secret_name="$2"
  local plain="$3"

  if [[ ! -f "${env_file}" ]]; then
    echo "ERROR: ${env_file} not found."
    exit 1
  fi

  ./k3s.sh create secret generic "${secret_name}" \
    --namespace "${NAMESPACE}" \
    --from-env-file="${env_file}" \
    --dry-run=client -o yaml > "${plain}"
  ./k3s.sh apply -f "${plain}"
}

seal_one "${SCRIPT_DIR}/.env" app-env \
  "${SCRIPT_DIR}/plain-secret.yaml"

for job in laravel-ecomerce-migrate; do
  ./k3s.sh delete job "${job}" -n "${NAMESPACE}" --ignore-not-found
done

./k3s.sh apply -f "${SCRIPT_DIR}/job-migrate.yaml"

for job in laravel-ecomerce-migrate; do
  ./k3s.sh wait --for=condition=complete "job/${job}" -n "${NAMESPACE}" --timeout=300s
done

./k3s.sh apply -f "${SCRIPT_DIR}/deployment.yaml"
./k3s.sh apply -f "${SCRIPT_DIR}/deployment-queue.yaml"
./k3s.sh apply -f "${SCRIPT_DIR}/service.yaml"
./k3s.sh apply -f "${SCRIPT_DIR}/ingress.yaml"

for deploy in laravel-ecomerce; do
  ./k3s.sh rollout restart "deployment/${deploy}" -n "${NAMESPACE}"
  ./k3s.sh rollout status "deployment/${deploy}" -n "${NAMESPACE}" --timeout=300s
done

echo ""
echo "Deployed platform. Verify:"
echo "  ./k3s.sh -n ${NAMESPACE} get pods,svc,ingress,cronjob"
echo "  curl -I https://laravel-ecomerce.test/"
