from fastapi import FastAPI, HTTPException
from pydantic import BaseModel, Field
from typing import List, Dict
from ortools.sat.python import cp_model

app = FastAPI()

class Timeslot(BaseModel):
    id: int
    day: int
    slot: int

class Teaching(BaseModel):
    group_id: int
    teacher_id: int
    subject_id: int
    weekly_slots: int

class SolveRequest(BaseModel):
    timeslots: List[Timeslot]
    groups: List[Dict]
    teaching: List[Teaching]
    soft: Dict = Field(default_factory=dict)  # optional preferences

class SolveResponse(BaseModel):
    assignments: List[Dict]
    
class Locked(BaseModel):
    group_id: int
    teacher_id: int
    subject_id: int
    timeslot_id: int

class ValidateRequest(SolveRequest):
    locked: List[Locked] = []

@app.get("/health")
def health():
    return {"ok": True}

@app.post("/validate")
def validate(req: ValidateRequest):
    # Check clashes in locked placements
    seen_group: Dict[Tuple[int,int], int] = {}
    seen_teacher: Dict[Tuple[int,int], int] = {}
    violations = []

    for i, a in enumerate(req.locked):
        gts = (a.group_id, a.timeslot_id)
        tts = (a.teacher_id, a.timeslot_id)
        if gts in seen_group:
            violations.append({"type":"group_conflict","timeslot_id":a.timeslot_id,"group_id":a.group_id})
        else:
            seen_group[gts] = 1
        if tts in seen_teacher:
            violations.append({"type":"teacher_conflict","timeslot_id":a.timeslot_id,"teacher_id":a.teacher_id})
        else:
            seen_teacher[tts] = 1

    return {"ok": len(violations) == 0, "violations": violations}

@app.post("/solve", response_model=SolveResponse)
def solve(req: SolveRequest):
    timeslots = req.timeslots
    teachings = req.teaching

    # Index helpers
    TS_IDS = [t.id for t in timeslots]

    model = cp_model.CpModel()

    # Decision vars: x[(g,tchr,subj,ts)] in {0,1}
    x = {}
    for a in teachings:
        for ts in TS_IDS:
            x[(a.group_id, a.teacher_id, a.subject_id, ts)] = model.NewBoolVar(f"x_g{a.group_id}_t{a.teacher_id}_s{a.subject_id}_ts{ts}")

    # 1) Each (group,subject) must be scheduled exactly weekly_slots times.
    from collections import defaultdict
    demand = defaultdict(int)
    for a in teachings:
        demand[(a.group_id, a.subject_id)] += a.weekly_slots

    for (g, s), need in demand.items():
        model.Add(
            sum(v for (gg, tt, ss, ts), v in x.items() if gg==g and ss==s) == need
        )

    # 2) A group cannot have more than one class in the same timeslot.
    for ts in TS_IDS:
        for g in {a.group_id for a in teachings}:
            model.Add(
                sum(v for (gg, tt, ss, tss), v in x.items() if tss==ts and gg==g) <= 1
            )

    # 3) A teacher cannot teach two groups at the same timeslot.
    for ts in TS_IDS:
        for t in {a.teacher_id for a in teachings}:
            model.Add(
                sum(v for (gg, tt, ss, tss), v in x.items() if tss==ts and tt==t) <= 1
            )

    # 4) Optional soft constraints (example: avoid last period)
    # soft = {"avoid_last_period_weight": 1}
    weight = int(req.soft.get("avoid_last_period_weight", 0))
    if weight > 0:
        last_periods = [ts.id for ts in timeslots if ts.slot == max(t.slot for t in timeslots)]
        penalty_vars = []
        for (g,tchr,sbj,ts), var in x.items():
            if ts in last_periods:
                p = model.NewIntVar(0, 1, f"penalty_{g}_{tchr}_{sbj}_{ts}")
                model.Add(p == var)
                penalty_vars.append(p)
        model.Minimize(sum(penalty_vars) * weight)

    # If no objective, still need something:
    if model.Proto().objective is None:
        model.Maximize(0)

    solver = cp_model.CpSolver()
    solver.parameters.max_time_in_seconds = 30.0

    status = solver.Solve(model)
    if status not in (cp_model.OPTIMAL, cp_model.FEASIBLE):
        raise HTTPException(status_code=422, detail="No feasible solution.")

    assignments = []
    for (g,tchr,sbj,ts), var in x.items():
        if solver.Value(var) == 1:
            assignments.append({
                "group_id": g,
                "teacher_id": tchr,
                "subject_id": sbj,
                "timeslot_id": ts
            })

    return SolveResponse(assignments=assignments)
