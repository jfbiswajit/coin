const formatter = new Intl.NumberFormat('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

export const fmtCurrency = (v: number): string => `৳${formatter.format(v)}`;
