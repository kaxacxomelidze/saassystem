import { useEffect, useState } from "react";
import { api, setAuth, setWorkspace } from "./api";

export default function App() {
  const [token, setToken] = useState(localStorage.getItem("token") || "");
  const [workspaces, setWorkspaces] = useState([]);
  const [workspaceId, setWorkspaceId] = useState(Number(localStorage.getItem("workspaceId")) || 0);
  const [conversations, setConversations] = useState([]);
  const [selected, setSelected] = useState(null);

  async function login() {
    const res = await api.post("/login", { email: "owner@movoer.test", password: "password123" });
    const nextToken = res.data.token;
    localStorage.setItem("token", nextToken);
    setToken(nextToken);
    setAuth(nextToken);
  }

  async function loadWorkspaces() {
    const res = await api.get("/workspaces");
    setWorkspaces(res.data.workspaces || []);
    if (!workspaceId && res.data.workspaces?.length) {
      const first = res.data.workspaces[0].id;
      setWorkspaceId(first);
      localStorage.setItem("workspaceId", String(first));
    }
  }

  async function loadInbox() {
    if (!workspaceId) return;
    setWorkspace(workspaceId);
    const res = await api.get("/inbox");
    setConversations(res.data.conversations?.data || []);
  }

  async function loadConversation(id) {
    setWorkspace(workspaceId);
    const res = await api.get(`/inbox/${id}`);
    setSelected(res.data.conversation);
  }

  useEffect(() => {
    if (!token) return;
    setAuth(token);
    loadWorkspaces();
  }, [token]);

  useEffect(() => {
    if (!token || !workspaceId) return;
    loadInbox();
  }, [token, workspaceId]);

  if (!token) {
    return <button onClick={login}>Login Demo</button>;
  }

  return (
    <div style={{ display: "grid", gridTemplateColumns: "320px 1fr", height: "100vh", fontFamily: "system-ui" }}>
      <div style={{ borderRight: "1px solid #eee", overflow: "auto" }}>
        <h3 style={{ padding: 12 }}>MOVOER Inbox</h3>
        <select style={{ margin: 12, width: "calc(100% - 24px)" }} value={workspaceId} onChange={(e) => setWorkspaceId(Number(e.target.value))}>
          <option value={0}>Select workspace</option>
          {workspaces.map((ws) => (
            <option key={ws.id} value={ws.id}>{ws.name}</option>
          ))}
        </select>
        {conversations.map((c) => (
          <div key={c.id} onClick={() => loadConversation(c.id)} style={{ padding: 12, borderTop: "1px solid #f3f3f3", cursor: "pointer" }}>
            <strong>{c.contact?.name || c.contact?.email || "Unknown"}</strong>
            <div style={{ fontSize: 12, opacity: 0.7 }}>{c.channel} · {c.status} · {c.priority}</div>
          </div>
        ))}
      </div>
      <div style={{ padding: 12, overflow: "auto" }}>
        {!selected ? <div>Select a conversation.</div> : (
          <>
            <h2>{selected.contact?.name || selected.contact?.email || "Conversation"}</h2>
            {(selected.messages || []).map((m) => (
              <div key={m.id} style={{ background: "#fafafa", borderRadius: 8, padding: 10, marginBottom: 8 }}>
                <div style={{ fontSize: 12 }}>{m.direction} · {m.sender}</div>
                <div>{m.body}</div>
              </div>
            ))}
          </>
        )}
      </div>
    </div>
  );
}
