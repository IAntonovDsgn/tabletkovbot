#!/usr/bin/env bash
set -euo pipefail

# Usage: scripts/apply-env.sh <env-file>   (KEY=VALUE lines come from stdin)
# - Creates <env-file> from <env-file>.example if it is missing.
# - Overwrites existing keys, appends missing ones.
# - Empty values are skipped (an empty secret never wipes a server value).

if [[ $# -ne 1 ]]; then
    echo "Usage: $0 <env-file>  (KEY=VALUE lines on stdin)" >&2
    exit 2
fi

env_file=$1
tmp_file="${env_file}.tmp.$$"
trap 'rm -f "$tmp_file"' EXIT

if [[ ! -f $env_file ]]; then
    example="${env_file}.example"
    if [[ ! -f $example ]]; then
        echo "[✗] $env_file not found and $example is missing" >&2
        exit 1
    fi
    cp "$example" "$env_file"
    chmod 600 "$env_file"
    echo "[✓] $env_file created from $example"
fi

applied=0
skipped=0

while IFS= read -r line || [[ -n ${line:-} ]]; do
    line=${line%$'\r'}
    [[ -z $line || $line == \#* || $line != *=* ]] && continue

    key=${line%%=*}
    value=${line#*=}

    if [[ ! $key =~ ^[A-Za-z_][A-Za-z0-9_]*$ ]]; then
        echo "[!] malformed key skipped: $key" >&2
        skipped=$((skipped + 1))
        continue
    fi

    if [[ -z $value ]]; then
        echo "[=] $key is empty, keeping current value" >&2
        skipped=$((skipped + 1))
        continue
    fi

    found=0
    rm -f "$tmp_file"
    while IFS= read -r existing || [[ -n ${existing:-} ]]; do
        case $existing in
            "$key="*)
                printf '%s=%s\n' "$key" "$value" >>"$tmp_file"
                found=1
                ;;
            *)
                printf '%s\n' "$existing" >>"$tmp_file"
                ;;
        esac
    done <"$env_file"

    if [[ $found -eq 0 ]]; then
        printf '%s=%s\n' "$key" "$value" >>"$tmp_file"
    fi

    mv "$tmp_file" "$env_file"
    chmod 600 "$env_file"
    echo "[✓] $key set"
    applied=$((applied + 1))
done

echo "[=] applied: $applied, skipped: $skipped"
