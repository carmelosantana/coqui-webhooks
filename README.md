# coqui-toolkit-webhooks

Optional [Coqui](https://github.com/carmelosantana/coqui) mod that adds the
**incoming webhooks** feature: an HTTP receiver endpoint, management CRUD API,
HMAC verifiers (GitHub, Slack, and a generic scheme), delivery dispatch, and the
webhook agent toolkit.

Core Coqui no longer ships webhooks — it is a clean leaf feature that consumes
core (creating tasks and triggering agents) but that nothing in core depends on.
Installing this mod restores the full feature through two additive extension
points, with no core changes.

## Install

```bash
/mods install coquibot/coqui-toolkit-webhooks
```

The mod self-registers on two surfaces:

- **HTTP routes** — the receiver and management API register via
  `extra.php-agents.apiFeatures` (`WebhooksFeature`), which Coqui's API server
  discovers and wires onto its router.
- **Agent toolkit** — `WebhookToolkit` registers via
  `extra.php-agents.toolkits` and builds its store from the shared Coqui SQLite
  database through the toolkit-discovery context.

No configuration needed. When the mod is not installed, webhook routes return
404, the toolkit is absent, and the health endpoint omits its webhook block.
