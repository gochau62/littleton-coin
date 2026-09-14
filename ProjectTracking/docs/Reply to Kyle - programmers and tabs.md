# Reply to Kyle — programmers, labels, tabs

**Subject:** RE: Project Tracking

---

Hey Kyle,

Thanks for taking a look.

**Dotted underlines** — fixed. The labels were drawing the line across the
whole field; they only run under their own text now, same as the current
screen.

**Multi-programmer** — it's a new file, `PRPGMRASGT`, keyed on project +
programmer. `PRPROJP` isn't touched; `PRPGMR` on it is still the primary
programmer, same as today. Each extra programmer on the new file carries
their own work status and scheduled start date, plus who added them and
when. One procedure, `PRJTRK002S`, is the only thing that reads or writes
it, and if the file isn't there the screen just shows the single programmer
like it does now. None of the existing `PROJ_` files were changed.

**Payback / Steering Committee** — those are still two separate tabs, same
as the current screen. Nothing was combined there, so no change for the
users.

Let me know what you find when you get into it on dev.

Thanks,
Gordon
