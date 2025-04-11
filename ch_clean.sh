#!/bin/bash

if [ -z "$1" ]; then
    echo "❌ Ошибка: Укажи префикс партиции, например: $0 202502"
    exit 1
fi

PARTITION_PREFIX="$1"
LOG_FILE="/var/log/clickhouse_cleanup.log"
TARGET_DIR="/var/lib/clickhouse/store"
DATE=$(date '+%Y-%m-%d %H:%M:%S')

echo "[$DATE] === Начало очистки ClickHouse (шаблон: $PARTITION_PREFIX*) ===" >> "$LOG_FILE"

# Остановка ClickHouse
echo "[$DATE] ⏹ Останавливаем ClickHouse..." | tee -a "$LOG_FILE"
systemctl stop clickhouse-server

# Поиск и удаление
echo "[$DATE] 🧹 Ищем и удаляем директории с шаблоном '$PARTITION_PREFIX*'..." | tee -a "$LOG_FILE"
find "$TARGET_DIR" -type d -name "${PARTITION_PREFIX}*" | while read -r dir; do
    echo "[$DATE] 🗑 Удаляем: $dir" | tee -a "$LOG_FILE"
    rm -rf "$dir"
done

# Запуск ClickHouse
echo "[$DATE] ▶️ Запускаем ClickHouse..." | tee -a "$LOG_FILE"
systemctl start clickhouse-server

echo "[$DATE] ✅ Очистка завершена" >> "$LOG_FILE"
