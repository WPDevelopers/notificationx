# Frontend Performance

Why the public bundle (`assets/public/js/frontend.js`) got so big, what we changed, and how we keep it small. The work started from a performance report by the xSpeed caching-plugin team (2026-09-22).

Start with the overview. If you only need to know what not to break, read the guardrails.

| Doc | What's inside |
| --- | --- |
| [00-overview.md](00-overview.md) | The xSpeed report, what we measured, the real root causes, and the phased plan (B0–B3) with status. |
| [01-optimizer-compatibility.md](01-optimizer-compatibility.md) | For caching/optimizer plugins: how to tell whether a page runs a GDPR notice, which hooks to purge on, edge cases. Also what we told xSpeed. |
| [02-b1-changes.md](02-b1-changes.md) | Phase B1 (shipped in the working tree, 2026-09-22): every change, why it is safe, the behaviour changes, and the verification results. |
| [03-guardrails.md](03-guardrails.md) | Rules and checks that stop the regression coming back: the import boundary, the bundle budget, and how to run every test. |
| [04-roadmap.md](04-roadmap.md) | Phases B2 (CSS) and B3 (consent loader split): plan, risks, and what must keep working. |

QA checklist for this work: [../../qa/frontend-performance.md](../../qa/frontend-performance.md).
