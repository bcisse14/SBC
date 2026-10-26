import { mkdir, writeFile } from "node:fs/promises";
import { fileURLToPath } from "node:url";

const origin = process.env.DEMO_SOURCE_URL || "http://localhost:8080";
const root = new URL("../", import.meta.url);
const response = await fetch(new URL("/api/site/bootstrap", origin));
if (!response.ok) throw new Error(`Export des contenus impossible : HTTP ${response.status}`);
const data = await response.json();
delete data.settings.notification_email;
delete data.settings.mail_from;
for (const key of Object.keys(data.content)) {
  if (key.startsWith("email.")) delete data.content[key];
}
await mkdir(new URL("public/demo/media/", root), { recursive: true });
await mkdir(new URL("src/data/", root), { recursive: true });
const extensions = { "image/jpeg": "jpg", "image/png": "png", "image/webp": "webp", "image/avif": "avif" };
for (const [id, media] of Object.entries(data.media)) {
  if (!/^\d+$/.test(id)) throw new Error(`Identifiant média invalide : ${id}`);
  const image = await fetch(new URL(`/api/media/${id}`, origin));
  if (!image.ok) throw new Error(`Export de la photo ${id} impossible : HTTP ${image.status}`);
  const extension = extensions[image.headers.get("content-type")?.split(";")[0]];
  if (!extension) throw new Error(`Format non pris en charge pour la photo ${id}`);
  media.demoPath = `/demo/media/${id}.${extension}`;
  media.variants = [];
  await writeFile(new URL(`public${media.demoPath}`, root), new Uint8Array(await image.arrayBuffer()));
}
const target = new URL("src/data/demo-site.json", root);
await writeFile(target, `${JSON.stringify(data, null, 2)}\n`);
console.log(`Démonstration exportée : ${data.pages.length} pages, ${Object.keys(data.media).length} photos (${fileURLToPath(target)}).`);
