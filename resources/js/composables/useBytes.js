const units = ['B', 'KB', 'MB', 'GB'];

export const formatBytes = (bytes) => {
    let value = Number(bytes ?? 0);
    let unit = 0;

    while (value >= 1024 && unit < units.length - 1) {
        value /= 1024;
        unit += 1;
    }

    return `${unit === 0 ? value : value.toFixed(1)} ${units[unit]}`;
};
