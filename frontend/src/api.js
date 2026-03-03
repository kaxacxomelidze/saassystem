import axios from "axios";

export const api = axios.create({
  baseURL: "http://127.0.0.1:8080/api",
});

export function setAuth(token) {
  api.defaults.headers.common.Authorization = `Bearer ${token}`;
}

export function setWorkspace(workspaceId) {
  api.defaults.headers.common["X-Workspace-Id"] = workspaceId;
}
