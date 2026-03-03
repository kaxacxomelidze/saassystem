import { useEffect, useMemo, useState } from "react";
import { api, setAuth, setWorkspace } from "./api";

export default function App() {
  const [token, setToken] = useState(localStorage.getItem("token") || "");
  const [workspaces, setWorkspaces] = useState([]);
  const [workspaceId, setWorkspaceId] = useState(Number(localStorage.getItem("workspaceId")) || 0);

  const [filters, setFilters] = useState({ status: "", priority: "" });
  const [conversations, setConversations] = useState([]);
  const [selectedId, setSelectedId] = useState(null);
  const [selected, setSelected] = useState(null);

  const [loginForm, setLoginForm] = useState({ email: "owner@movoer.test", password: "password123" });
  const [error, setError] = useState("");

  const canUse = useMemo(() => !!token, [token]);

  async function login() {
    setError("");
    try {
      const res = await api.post("/login", loginForm);
      const nextToken = res.data.token;
      localStorage.setItem("token", nextToken);
      setToken(nextToken);
      setAuth(nextToken);
      await loadWorkspaces();
    } catch (err) {
      setError(err?.response?.data?.message || "Login failed");
    }
  }

  async function loadWorkspaces() {
    const res = await api.get("/workspaces");
    const list = res.data.workspaces || [];
    setWorkspaces(list);

    if (!workspaceId && list.length) {
      const firstId = list[0].id;
      setWorkspaceId(firstId);
      localStorage.setItem("workspaceId", String(firstId));
    }
  }

  async function loadInbox() {
    if (!workspaceId) return;

    setWorkspace(workspaceId);

    const params = {};
    if (filters.status) params.status = filters.status;
    if (filters.priority) params.priority = filters.priority;

    const res = await api.get("/inbox", { params });
    setConversations(res.data.conversations?.data || []);
  }

  async function loadConversation(id) {
    setWorkspace(workspaceId);
    const res = await api.get(`/inbox/${id}`);
    setSelected(res.data.conversation);
  }

  async function aiDraft() {
    if (!selectedId) return;

    setWorkspace(workspaceId);
    const res = await api.post(`/ai/${selectedId}/draft`, { tone: "professional", language: "English" });
    alert(res.data.draft || "No draft generated");
  }

  async function connectGmail() {
    setWorkspace(workspaceId);
    const res = await api.get("/gmail/auth-url");
    window.location.href = res.data.url;
  }

  async function syncNow() {
    setWorkspace(workspaceId);
    await api.post("/gmail/sync-now");
    alert("Sync queued. Refresh inbox in 10-30 seconds.");
  }

  useEffect(() => {
    if (!token) return;

    setAuth(token);
    loadWorkspaces();
  }, [token]);

  useEffect(() => {
    if (!canUse || !workspaceId) return;

    localStorage.setItem("workspaceId", String(workspaceId));
    loadInbox();
  }, [workspaceId, filters, canUse]);

  useEffect(() => {
    if (!selectedId) return;

    loadConversation(selectedId);
  }, [selectedId]);

  if (!token) {
    return (
      <div style={{ maxWidth: 420, margin: "60px auto", fontFamily: "system-ui" }}>
        <h2>MOVOER Login</h2>
        <input
          placeholder="Email"
          value={loginForm.email}
          onChange={(e) => setLoginForm({ ...loginForm, email: e.target.value })}
          style={{ width: "100%", padding: 10, marginBottom: 10 }}
        />
        <input
          placeholder="Password"
          type="password"
          value={loginForm.password}
          onChange={(e) => setLoginForm({ ...loginForm, password: e.target.value })}
          style={{ width: "100%", padding: 10, marginBottom: 10 }}
        />
        <button onClick={login} style={{ width: "100%", padding: 10 }}>Login</button>
        <p style={{ opacity: 0.7, marginTop: 12 }}>Demo: owner@movoer.test / password123</p>
        {error ? <p style={{ color: "crimson" }}>{error}</p> : null}
      </div>
    );
  }

  return (
    <div style={{ height: "100vh", display: "grid", gridTemplateColumns: "260px 360px 1fr", fontFamily: "system-ui" }}>
      <div style={{ borderRight: "1px solid #eee", padding: 12 }}>
        <h3>MOVOER</h3>

        <div style={{ marginBottom: 10 }}>
          <div style={{ fontSize: 12, opacity: 0.7 }}>Workspace</div>
          <select
            value={workspaceId}
            onChange={(e) => setWorkspaceId(Number(e.target.value))}
            style={{ width: "100%", padding: 8 }}
          >
            <option value={0}>Select...</option>
            {workspaces.map((ws) => <option key={ws.id} value={ws.id}>{ws.name}</option>)}
          </select>
        </div>

        <div style={{ display: "grid", gap: 8, marginTop: 10 }}>
          <button onClick={connectGmail} style={{ padding: 10 }}>Connect Gmail</button>
          <button onClick={syncNow} style={{ padding: 10 }}>Sync Now</button>
        </div>

        <div style={{ marginTop: 14 }}>
          <div style={{ fontSize: 12, opacity: 0.7 }}>Status</div>
          <select
            value={filters.status}
            onChange={(e) => setFilters({ ...filters, status: e.target.value })}
            style={{ width: "100%", padding: 8, marginBottom: 8 }}
          >
            <option value="">All</option>
            <option value="open">Open</option>
            <option value="pending">Pending</option>
            <option value="closed">Closed</option>
          </select>

          <div style={{ fontSize: 12, opacity: 0.7 }}>Priority</div>
          <select
            value={filters.priority}
            onChange={(e) => setFilters({ ...filters, priority: e.target.value })}
            style={{ width: "100%", padding: 8 }}
          >
            <option value="">All</option>
            <option value="normal">Normal</option>
            <option value="important">Important</option>
            <option value="urgent">Urgent</option>
          </select>
        </div>

        <div style={{ marginTop: 14 }}>
          <button onClick={loadInbox} style={{ width: "100%", padding: 10 }}>Refresh</button>
        </div>
      </div>

      <div style={{ borderRight: "1px solid #eee", overflow: "auto" }}>
        {conversations.map((conversation) => (
          <div
            key={conversation.id}
            onClick={() => setSelectedId(conversation.id)}
            style={{
              padding: 12,
              cursor: "pointer",
              borderBottom: "1px solid #f2f2f2",
              background: selectedId === conversation.id ? "#f7f7f7" : "white",
            }}
          >
            <div style={{ display: "flex", justifyContent: "space-between" }}>
              <strong>{conversation.contact?.name || conversation.contact?.email || "Unknown"}</strong>
              <span style={{ fontSize: 12, opacity: 0.7 }}>{conversation.channel}</span>
            </div>
            <div style={{ fontSize: 13, opacity: 0.75, marginTop: 6 }}>
              {conversation.priority?.toUpperCase()} · {conversation.status}
            </div>
          </div>
        ))}
      </div>

      <div style={{ padding: 12, overflow: "auto" }}>
        {!selected ? (
          <div style={{ opacity: 0.7 }}>Select a conversation</div>
        ) : (
          <>
            <div style={{ display: "flex", justifyContent: "space-between", alignItems: "center" }}>
              <div>
                <h3 style={{ margin: 0 }}>
                  {selected.contact?.name || selected.contact?.email || "Conversation"}
                </h3>
                <div style={{ fontSize: 12, opacity: 0.7 }}>
                  {selected.channel} · {selected.status} · {selected.priority}
                </div>
              </div>
              <button onClick={aiDraft} style={{ padding: "10px 12px" }}>AI Draft</button>
            </div>

            <div style={{ marginTop: 12 }}>
              {(selected.messages || []).map((message) => (
                <div key={message.id} style={{ marginBottom: 10, padding: 10, background: "#fafafa", borderRadius: 8 }}>
                  <div style={{ fontSize: 12, opacity: 0.7 }}>
                    {message.direction?.toUpperCase()} · {message.sender} · {new Date(message.sent_at || message.created_at).toLocaleString()}
                  </div>
                  <div style={{ marginTop: 6 }}>{message.body}</div>
                </div>
              ))}
            </div>
          </>
        )}
      </div>
    </div>
  );
}
