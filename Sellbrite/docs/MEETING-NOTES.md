# Meeting Notes — System Improvements Project, Meeting 1

> **Faithful capture** of the meeting held **06/25/2026**. This file records what was
> discussed and decided. The actionable build plan derived from it lives in
> [`ROADMAP.md`](./ROADMAP.md).

---

## Attendees & roles

| Person | Role |
| --- | --- |
| **Tyler** | New consultant, brought in to optimize ProfileCoin's processes |
| **Des** | With the company since inception; **primary inventory controller** and main system user |
| **Lee** | Training to take over inventory-control functions (second heaviest user) |
| Hillary, Sue, Andrew | Determine product distribution channels; mostly use the system for **reporting/querying** |
| Dan | Reporting/querying user |

> ⚠️ **Naming note:** the transcript uses both **"Sellbrite"** and **"Cellbrite"/"Cellebrite."**
> It treats them as two systems (Sellbrite = order/listing management, Cellbrite = inventory).
> This is worth confirming — they may be the same platform or two distinct tools. Flagged in
> ROADMAP open questions.

---

## Quick recap

Introductory session between Tyler (new consultant) and Des (primary inventory controller).
ProfileCoin runs a **fragmented, three-platform system**:

- **Sellbrite** — order / listing management (~**$7,000/year**)
- **Cellbrite** — inventory control / order status (payment, shipping)
- **ShipStation** — shipping

Each system operates **independently** and requires **extensive manual data entry and
spreadsheet workarounds** to stay in sync. A recent **eBay policy change broke the entire
pipeline**. Products are entered into a **complex Excel/ODS bulk-upload spreadsheet** that
feeds the various marketplaces.

**Throughput:** multiple batches of **30–50 coins daily**. ~**80%** are common US coins
(<1 min each via spreadsheet formulas); ~**20%** are one-off, rare, or foreign items that can
take **30–60 min each** due to research.

---

## Next steps (from the meeting)

**Des**
- Continue daily product uploads and system checks.
- Be available for a follow-up meeting on pricing.

**Tyler**
- Review the "Inventory Replenishment Data Entry" pricing spreadsheet; prep a pricing follow-up.
- Investigate **alternatives to Sellbrite** and the feasibility of a **custom software solution**
  for the syncing / workflow problems.
- Email Des with follow-up clarification questions as needed.

---

## Topic-by-topic summary

### 1. E-commerce system challenges
Sellbrite is primary; Cellbrite handles inventory; ShipStation handles shipping. They **don't
integrate well**, forcing manual work and complex spreadsheets to keep data in sync. Sellbrite
costs ~$7K/year but has **limited reporting** — Des manually exports and processes raw data to
produce accurate sales reports that account for **discounts and taxes**.

### 2. Cellbrite product-listing management
Des likes Cellbrite when it works, but **manually purges old listings every few months** to
keep performance up (especially "what-you-see-is-what-you-get" items, which pile up). Purged
data is archived in a **master VLOOKUP spreadsheet with multiple tabs** for historical
retrieval. Cellbrite tracks **current order status** (payment, shipping).

### 3. Product-upload platform process
After uploading a CSV to Cellbrite, users must **create listings per product** through
Cellbrite's UI. **Amazon** is harder — it requires creating **ASINs manually** and can't be
done directly through Cellbrite. **Walmart** is uncertain due to ongoing platform changes. The
current **bulk file format was inspired by eBay and Amazon bulk loaders** and aims to
**consolidate all-platform info in one place**.

### 4. Coin-listing workflow
Physical product is received from **Littleton** in trays with item numbers and labels. ~**80%**
are common US coins processed efficiently with **automated spreadsheet formulas**; the other
~**20%** (paper money, foreign coins) need significant research. Resources include the
**Redbook** and online databases like **Greysheet**, but the process leans heavily on **manual
coin knowledge** and slows for non-US items.

### 5. Coin product-input process
~**1/3** of inventory arrives **certified** from major graders (**NGC, PCGS**); the rest are
graded **in-house** to **Littleton specifications**. Common listings: <1 min each. One-offs:
30–60 min (research). Multiple batches of **30–50 coins/day**. Des updates **dropdown options
almost daily** and makes **major product additions a few times per month**.

### 6. Backend system updates
Des regularly makes **minor backend tweaks** — new denominations, varieties, formatting changes
driven by platform updates (e.g., **Amazon's character-limit reduction**). ~**80%** of the time
formulas handle common US coins; foreign/ancient/unique items get **manual custom descriptions**.
**Distribution channels** are decided by **Hillary, Sue, and Andrew**; most products go to all
stores unless marked **website-exclusive** (communication about exclusivity "could be improved").

### 7. Amazon listing management
Amazon uploads happen **twice a month**, syncing **~300–500 SKUs**. Cellbrite handles inventory
and price syncing but **lags** other platforms (e.g., eBay) on updates. Uses a **"don't list on
Amazon" filter** and various spreadsheet flags to manage categories and compliance (dates, mint
marks, etc.).

### 8. Amazon & eBay upload systems
They'd prefer **"set and forget"** for Amazon, but the process needs **manual control** for data
anomalies and formatting. Amazon's **required values haven't changed in 7 years** but remain
**very strict on formatting**. For **eBay Live**, a **simplified spreadsheet** handles live
streaming sales — minimal product detail, but increasingly complex over time for **tax
compliance**.

### 9. eBay Live show process
**One show/day**, ~**150 items/show**, selected by management + host (with flexibility). eBay
Live products are **exclusive to that platform** — they don't transfer to the website or Amazon.
Unsold items **may be reassigned new SKUs** if moved to other channels. Des manages the Cellbrite
side of eBay Live but doesn't track what happens to unsold items afterward.

### 10. Image processing & pricing workflow
Photos are taken and edited in **GIMP**, named with **specific SKU patterns** to link
image → product. Pricing was once set by buyers; now the company prices independently using
**cost averages from the AS/400** and **market values (e.g., Greysheet)**, via a **complex
pricing spreadsheet**. Des **wants this pricing process automated** in the future.

---

## Decisions & signals for the build

- ✅ The **bulk-upload spreadsheet is the heart of the workflow** — this is exactly what the
  Sellbrite Bulk Loader screen replaces/augments.
- ✅ **~80% formula-driven, ~20% manual** → automation should target the slow 20% (foreign /
  one-off descriptions) and the repetitive 80% (validation, defaults).
- ✅ **Marketplace formatting is strict and changes** (Amazon char limits, eBay policy) →
  validation + pre-flight checks are high value.
- ✅ **Pricing automation** is an explicit future wish (AS/400 cost + market value).
- ✅ **Syncing across Sellbrite / Cellbrite / ShipStation** is the core pain → consolidation in
  one place is the goal.
- ❓ Open: Sellbrite vs Cellbrite naming; channel-exclusivity communication; what happens to
  unsold eBay Live items.
