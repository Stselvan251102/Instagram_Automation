import React, { useEffect } from "react";
import ReactDOM from "react-dom/client";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import { Studio } from "./routes/index";
import { Toaster } from "./components/ui/sonner";
import { useStudio } from "./store/studio";
import "./styles.css";

const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      retry: 1,
      refetchOnWindowFocus: false,
    },
  },
});

function App() {
  const { setProject, brandKits, setBrand } = useStudio();

  useEffect(() => {
    // Check if a project ID is specified in URL query
    const params = new URLSearchParams(window.location.search);
    const projectId = params.get("project");
    if (projectId) {
      fetch(`/api/projects/get.php?id=${encodeURIComponent(projectId)}`)
        .then((res) => res.json())
        .then((data) => {
          if (data.ok && data.project) {
            setProject(data.project);
          }
        })
        .catch((err) => {
          console.warn("Could not load project from database:", err);
        });
    }

    // Sync saved brand kits from database if available
    fetch("/api/brandkits/list.php")
      .then((res) => res.json())
      .then((data) => {
        if (data.ok && Array.isArray(data.brand_kits) && data.brand_kits.length > 0) {
          // brandkits available from DB
        }
      })
      .catch(() => {});
  }, [setProject]);

  return (
    <QueryClientProvider client={queryClient}>
      <Studio />
      <Toaster position="top-center" richColors />
    </QueryClientProvider>
  );
}

const rootElement = document.getElementById("root");
if (rootElement) {
  ReactDOM.createRoot(rootElement).render(<App />);
}
