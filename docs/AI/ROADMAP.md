# AI/NPC Roadmap — Solo Play and Beyond

> One human. Hundreds of intelligent factions. Friends, foes, neutrals — all thinking, trading, building, plotting.
>
> This roadmap distills the AI vision in docs/AI/ into a phased, shippable plan.

## 🎯 Vision (TL;DR)
- Local‑first LLM + deterministic game logic
- Cohesive NPC behavior across economy, combat, diplomacy
- Multi‑agent alliances with shared intel and synchronized ops
- Ethical, explainable AI with readable difficulty scaling
- Production‑grade ops: tests, dashboards, telemetry, replays

### System Map

```mermaid
flowchart LR
    Human(Human Player)
    NPCs[NPC Agents (Alliance, Raider, Scout, Trader, Defender)]
    LLM[vLLM/Ollama Inference]
    Orchestrator[GPU Orchestrator]
    Engine[Game Engine\n(Economy/Combat/Diplomacy)]
    Telemetry[Prometheus/Grafana]
    Storage[(AI Memory\n+ Telemetry DB)]

    Human <---> Engine
    NPCs --> Engine
    Engine --> LLM
    LLM --> Orchestrator
    Engine --> Telemetry
    NPCs <--> Storage
    Engine <--> Storage
```

## 🧭 Pillars (from the AI docs)
- Framework: AI-FRAMEWORK-MASTER-BLUEPRINT.md, AI-Framework-Vision.md, AI-SYSTEM-COMPLETE-SUMMARY.md
- Behavior: NPC-BEHAVIOR-SYSTEM.md, AI-NPC-OVERVIEW.md
- Economy/Build: BUILDING-ECONOMY-ENGINE.md
- Combat: COMBAT-AI-SYSTEM.md
- Diplomacy/Alliances: DIPLOMACY-ALLIANCE-AI.md
- Multi-Agent: MULTI-AGENT-COORDINATION.md
- Learning: CROSS-WORLD-LEARNING.md
- Personality: PERSONALITY-PSYCHOLOGY.md
- Advanced Tactics: ADVANCED-STRATEGIES.md
- LLM Integration: LOCAL-LLM-INTEGRATION.md, API-INTEGRATION-LAYER.md
- Data/Architecture: DATA-MODELS-ARCHITECTURE.md
- Performance: PERFORMANCE-SCALING.md
- Quality/DevOps: TESTING-MONITORING-DEVOPS.md
- Ethics/Balance: AI-ETHICS-BALANCE.md

## 🗺️ Phased Delivery Plan

> Ship progress in fun, playable slices. Each phase has a clear demo and gate checks.

```mermaid
gantt
    title AI Roadmap (High‑level)
    dateFormat  YYYY-MM
    section Foundations
    Phase 0:done, p0, 2025-10, 1m
    section Core Loop
    Phase 1:active, p1, 2025-11, 1m
    section Alliances
    Phase 2: p2, 2025-12, 1m
    section Progression
    Phase 3: p3, 2026-01, 1m
    section Ops & Scale
    Phase 4: p4, 2026-02, 1m
```

### Phase 0 — Foundations (Infra, Telemetry, Guards)
- Local LLM serving (vLLM/Ollama), request routing, caching
- Agent loop skeletons, queues, time budgets, backpressure
- Prometheus metrics, Grafana dashboards, structured logs
- Test harnesses for economy/combat; replay mechanism

Milestone Demo: 10 idle NPCs alive, producing metrics and safe no‑ops.

---

### Phase 1 — Core Solo Loop (Economy + Basic Combat + Basic Diplomacy)
- Economy/build queue engine; resource optimization; convoy routes
- Battle simulator v1; scouting; risk‑aware raids; retaliations
- Diplomacy thresholds: neutrality, friendship, hostility; simple treaties (NAP, Ally, War)
- Explainable decisions: short "why" notes attached to important actions

Milestone Demo: 1 human vs 20 NPCs; balanced raids, visible treaties, readable reasons.

---

### Phase 2 — Alliances and Coordination
- Roles: leader, scout, raider, defender; dynamic reassignment
- Shared intelligence network; synchronized multi‑target strikes
- Negotiation playbooks; tribute/trade; betrayal thresholds

Milestone Demo: 1 human + 2 AI allies vs 30 NPCs; timed joint operations.

---

### Phase 3 — Progression, Personality, Advanced Strategy
- Personality traits and archetypes; adaptive moods
- Curriculum/self‑play ladders; cross‑world learning store
- Deception/feints, multi‑front pressure, eco‑bait tactics
- Difficulty scaling profiles with guardrails

Milestone Demo: Season narrative with rivalries; difficulty ladder from "Chill" to "Warlord".

---

### Phase 4 — Ops, Scale, and Polish
- Priority queues; batch generation; GPU scheduling; multihost
- Load testing suites; alarms; success SLOs
- Seasonal replays; balance/tuning dashboards

Milestone Demo: 1 human vs 200 NPCs stable for 24h; insights dashboard.

## 🧪 Gate Checks (per phase)
- Functional: economy ticks, raids, treaties, roles
- Performance: p95 decision latency within budget
- Observability: dashboards populated, alerts quiet
- Explainability: top actions show short reasons
- Stability: ≥N hours without crash; memory steady

### Milestone Checklist
- [ ] P0: 10 idle NPCs with healthy telemetry
- [ ] P1: 1 human vs 20 NPCs balanced demo
- [ ] P2: Coordinated alliance strike with roles
- [ ] P3: Personality + advanced strategies active
- [ ] P4: 1 human vs 200 NPCs stable 24h

## 🧩 NPC Role Kit (starter set)
- Scout — intel, pathing, report
- Raider — strike, evaluate losses, target next
- Defender — garrison, repair, reinforce
- Trader — convoy optimizer, market haggler
- Leader — sets alliance plan, assigns roles

## 🎮 Solo Play Scenarios
- Human vs World (FFA)
- Co‑op with Allies (team AI)
- Betrayal Season (trust slowly erodes)
- Wonder Rush (race objectives)

## 🧱 Ethics & Balance
- No perfect information; cost for intel
- Human‑readable difficulty knobs
- Anti‑exploit guardrails; fair resource rules

## ⚙️ Performance & Scaling
- Priority queues and budgets per decision class
- Batch inference, speculative exec, cache layers
- GPU orchestrator with load‑aware routing

## 📊 Telemetry & Replays
- Prometheus metrics (decisions, queues, errors, latencies)
- Grafana: NPC overview, battles, economy, diplomacy
- Replays: compressed event logs, in‑game highlights

## 🧠 NPC Decision Loop (Sequence)

```mermaid
sequenceDiagram
    participant H as Human
    participant N as NPC Agent
    participant E as Game Engine
    participant L as LLM (vLLM/Ollama)
    participant C as Cache/Memory
    participant T as Telemetry
  
    N->>E: Sense world (resources, threats, treaties)
    E-->>N: State snapshot
    N->>C: Retrieve last decisions + memory
    alt Cache hit
      C-->>N: Prior rationale + result
      N->>E: Execute fast path
      E->>T: Emit metrics/logs
    else Needs new reasoning
      N->>L: Prompt with compact state + intent
      L-->>N: Plan + short reason (explainable)
      N->>E: Execute plan (build/trade/scout/raid)
      E->>C: Store memory & outcome
      E->>T: Emit metrics/logs
    end
```

## 🤝 Diplomacy State Machine

```mermaid
stateDiagram-v2
    [*] --> Neutral
    Neutral --> Contact: first interaction
    Contact --> Friendly: +reputation / successful trade
    Friendly --> Allied: treaty signed / shared ops
    Neutral --> Wary: raids / spying / broken deals
    Wary --> Hostile: repeated aggression
    Hostile --> War: declared conflict
    Allied --> Betrayal: threshold crossed
    Betrayal --> Wary
    War --> Truce: negotiation / exhaustion
    Truce --> Neutral
```

## 🕸️ Multi‑Agent Coordination

```mermaid
sequenceDiagram
    participant L as Alliance Leader
    participant S as Scouts
    participant R as Raiders
    participant D as Defenders
    participant X as Shared Intel
  
    L->>S: Recon targets window t0..t1
    S-->>X: Upload sightings, garrisons, routes
    L->>R: Assign synchronized strike (t*, paths)
    L->>D: Pre‑position reinforcements
    par Raiders
      R->>X: Request latest intel, confirm paths
      R->>R: Time sync and approach
    and Defenders
      D->>X: Monitor alerts, reserve stacks
    end
    R->>X: Post‑battle report
    L->>All: Evaluate outcome and adjust plan
```

## 🛠 Economy / Build Pipeline

```mermaid
flowchart LR
    Rsrc[Resources/Production] --> Need[Analyze Needs]
    Need -->|Shortfall| Trade[Trade/Market]
    Need -->|Sufficient| Queue[Build Queue]
    Trade --> Convoy[Convoy Planner]
    Queue --> Build[Construct/Upgrade]
    Convoy --> Depot[Deliver to Depot]
    Depot --> Build
    Build --> Tele[Telemetry]
```

## 🚦 LLM Routing & Orchestration

```mermaid
graph TB
    Client[NPC Decision Requests]
    Router[Prompt Router
    (priority, budget, cache)]
    Fast[Inference A
    (fast/cheap)]
    Deep[Inference B
    (deep/slow)]
    Cache[(LLM Cache)]
    
    Client --> Router
    Router -->|P<=3| Fast
    Router -->|P>3 or complex| Deep
    Router --> Cache
    Fast --> Cache
    Deep --> Cache
```

## 🗃 Data Model Overview (Simplified)

```mermaid
classDiagram
    class Agent {
      +id
      +archetype
      +traits
    }
    class Decision {
      +id
      +agentId
      +type
      +reason
      +timestamp
    }
    class Memory {
      +id
      +agentId
      +key
      +value
      +ttl
    }
    class Battle {
      +id
      +attacker
      +defender
      +result
    }
    class Treaty {
      +id
      +parties
      +type
      +status
    }
    class Alliance {
      +id
      +members
      +plan
    }
    class Telemetry {
      +metric
      +value
      +labels
      +ts
    }
    Agent --> Decision
    Agent --> Memory
    Alliance o-- Agent
    Treaty o-- Agent
    Decision --> Telemetry
    Battle --> Telemetry
```

## 📡 Observability Pipeline

```mermaid
flowchart LR
    App[Game + NPCs] --> Logs[Structured Logs]
    App --> Metrics[Prometheus Metrics]
    Metrics --> Prom[Prometheus]
    Logs --> Loki[Log Store]
    Prom --> Graf[Grafana Dashboards]
    Loki --> Graf
    Prom --> Alert[Alertmanager]
    Alert --> Oncall[On‑call]
```

## 🔗 Source Map (read next)
- AI-FRAMEWORK-MASTER-BLUEPRINT.md — end‑to‑end architecture
- LOCAL-LLM-INTEGRATION.md — run models locally (vLLM/Ollama)
- NPC-BEHAVIOR-SYSTEM.md — behavior pipeline
- DIPLOMACY-ALLIANCE-AI.md — treaties and betrayals
- COMBAT-AI-SYSTEM.md — battle decisions
- BUILDING-ECONOMY-ENGINE.md — economy/queues
- TESTING-MONITORING-DEVOPS.md — quality & ops

> PRs welcome. Keep docs/AI/ content authoritative; ROADMAP.md aggregates and links while we build.
