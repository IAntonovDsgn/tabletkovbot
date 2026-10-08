#!/usr/bin/env bash
set -euo pipefail

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

# 1. Читаем входящие переменные со stdin в ассоциативный массив
declare -A new_vars
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

    new_vars["$key"]="$value"
done

# 2. Обновляем существующий файл за один проход
while IFS= read -r line || [[ -n ${line:-} ]]; do
    clean_line=${line%$'\r'}
    matched=0

    for key in "${!new_vars[@]}"; do
        case $clean_line in
            "$key="*|"# $key="*|"#$key="*)
                printf '%s=%s\n' "$key" "${new_vars[$key]}" >> "$tmp_file"
                unset "new_vars[$key]"
                applied=$((applied + 1))
                matched=1
                echo "[✓] $key set"
                break
                ;;
        esac
    done

    if [[ $matched -eq 0 ]]; then
        printf '%s\n' "$clean_line" >> "$tmp_file"
    fi
done < "$env_file"

# 3. Дописываем ключи, которых не было в файле
for key in "${!new_vars[@]}"; do
    printf '%s=%s\n' "$key" "${new_vars[$key]}" >> "$tmp_file"
    applied=$((applied + 1))
    echo "[✓] $key appended"
done

mv "$tmp_file" "$env_file"
chmod 600 "$env_file"

echo "[=] applied: $applied, skipped: $skipped"
