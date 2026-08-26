import { readFile } from "node:fs/promises";
import { join } from "node:path";
import { ImageResponse } from "next/og";

export const size = { width: 180, height: 180 };
export const contentType = "image/png";

export default async function AppleIcon() {
  const logoMark = await readFile(join(process.cwd(), "public", "logo-mark.png"));
  const logoMarkSrc = `data:image/png;base64,${logoMark.toString("base64")}`;

  return new ImageResponse(
    (
      <div
        style={{
          width: "100%",
          height: "100%",
          display: "flex",
          alignItems: "center",
          justifyContent: "center",
          background: "#ffffff",
          borderRadius: 40,
          padding: 20,
        }}
      >
        {/* eslint-disable-next-line @next/next/no-img-element */}
        <img src={logoMarkSrc} width="100%" height="100%" style={{ objectFit: "contain" }} alt="" />
      </div>
    ),
    size,
  );
}
