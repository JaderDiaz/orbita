# Colombian Taxes

The four taxes are **not four of the same thing**. Each needs an explicit unit and base.

| Tax        | Rate | Unit       | Base               | Sign      |
| ---------- | ---- | ---------- | ------------------ | --------- |
| IVA        | 19   | percent    | subtotal           | adds      |
| Retefuente | 11   | percent    | subtotal (pre-IVA) | withholds |
| ReteICA    | 9.66 | **permil** | subtotal           | withholds |
| ReteIVA    | 15   | percent    | **the IVA amount** | withholds |

```sql
document_taxes(
  document_type, document_id, code, label,
  rate numeric(9,4),
  unit  text check (unit in ('percent','permil')),
  base  text check (base in ('subtotal','iva')),
  base_amount_minor bigint, amount_minor bigint, sign smallint)
```

- **ReteICA is per mil.** 9.66‰ = 0.966%. Treating it as a percent overcharges 10×.
- **ReteIVA's base is the IVA amount**, not the subtotal. Wrong base overcharges ~5×.
- Rounding: line subtotal → round half-up to minor units → sum → apply each tax to the
  **already-rounded** aggregate base → round half-up. Document level, not per line.
- Retention minimum bases (UVT thresholds) come from Parámetros; skip the tax below threshold.

## Snapshot on issue

Freeze on the document row when it is issued: client fiscal identity (tipo de documento,
documento, régimen, razón social, dirección), each tax's label/rate/unit/base, `currency`, and
for USD `trm_rate` + `trm_date` + `total_cop_minor`. Analytics always aggregate `*_cop_minor`.

Reference case — subtotal $10.000.000:
IVA $1.900.000 · Retefuente $1.100.000 · ReteICA $96.600 · ReteIVA $285.000
