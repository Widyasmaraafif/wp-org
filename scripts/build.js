const fs = require("fs");
const path = require("path");
const archiver = require("archiver");

const pluginFile = fs.readFileSync(
  path.join(__dirname, "..", "wp-org.php"),
  "utf8",
);
const versionMatch = pluginFile.match(/Version:\s+([0-9.]+)/);
if (!versionMatch) {
  console.error("Version not found in wp-org.php");
  process.exit(1);
}
const version = versionMatch[1];
const distDir = path.join(__dirname, "..", "dist");
const zipPath = path.join(distDir, `wp-org-${version}.zip`);
const pluginDir = path.join(__dirname, "..");

if (!fs.existsSync(distDir)) {
  fs.mkdirSync(distDir, { recursive: true });
}

if (fs.existsSync(zipPath)) {
  fs.unlinkSync(zipPath);
}

const output = fs.createWriteStream(zipPath);
const archive = archiver("zip", { zlib: { level: 9 } });

output.on("close", () => {
  const size = (archive.pointer() / 1024).toFixed(1);
  console.log(`Created ${path.basename(zipPath)} (${size} KB)`);
});

archive.on("error", (err) => {
  throw err;
});

archive.pipe(output);

const files = ["wp-org.php", "composer.json", "composer.lock"];

const dirs = ["src", "assets", "data"];

files.forEach((file) => {
  const filePath = path.join(pluginDir, file);
  if (fs.existsSync(filePath)) {
    archive.file(filePath, { name: `wp-org/${file}` });
  }
});

dirs.forEach((dir) => {
  const dirPath = path.join(pluginDir, dir);
  if (fs.existsSync(dirPath)) {
    archive.directory(dirPath, `wp-org/${dir}`);
  }
});

const vendorPath = path.join(pluginDir, "vendor");
if (fs.existsSync(vendorPath)) {
  archive.directory(vendorPath, "wp-org/vendor");
}

archive.finalize();
