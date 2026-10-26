export type EstimateContact = { name: string; address: string; email: string; phone: string };
export type EstimateCompany = EstimateContact & { registration: string; vatNumber: string; insurance: string };
export type EstimateLine = { description: string; quantity: string; unit: string; unitPrice: string; vatRate: string };
export type EstimateData = {
  title: string; date: string; validUntil: string; company: EstimateCompany; client: EstimateContact;
  siteAddress: string; introduction: string; lines: EstimateLine[]; depositPercent: string; conditions: string; notes: string;
};
export type EstimateTotals = {
  lines: Array<{ ht: number; vat: number; ttc: number }>;
  totalHt: number; totalVat: number; totalTtc: number; vatGroups: Record<string, number>; deposit: number; balance: number;
};
export type SavedEstimate = {
  id: number; number: string; requestId: number | null; data: EstimateData; totals: EstimateTotals; missing: string[]; updatedAt: string;
};
export type EstimateProfile =
  | { id: number; kind: "company"; data: EstimateCompany }
  | { id: number; kind: "client"; data: EstimateContact };
export const emptyContact: EstimateContact = { name: "", address: "", email: "", phone: "" };
export const emptyCompany: EstimateCompany = { ...emptyContact, registration: "", vatNumber: "", insurance: "" };
export const money = (cents: number) => new Intl.NumberFormat("fr-FR", { style: "currency", currency: "EUR" }).format(cents / 100);
