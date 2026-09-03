const formatter = new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND', maximumFractionDigits: 0 });

export const formatVnd = (amount) => formatter.format(Number(amount ?? 0));
