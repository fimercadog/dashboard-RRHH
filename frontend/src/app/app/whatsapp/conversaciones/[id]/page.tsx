import ConversacionDetailPage from "./ConversacionDetailPage";

export function generateStaticParams() {
  // Static export requires ≥1 path. Apache rewrite in public/.htaccess
  // serves this shell for any real conversation ID (URL unchanged in browser).
  return [{ id: "0" }];
}

export default function Page() {
  return <ConversacionDetailPage />;
}
