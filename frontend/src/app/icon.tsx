import { readFile } from "node:fs/promises";
import { join } from "node:path";
import { ImageResponse } from "next/og";

export const size = { width: 32, height: 32 };
export const contentType = "image/png";

export default async function Icon() {
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
          borderRadius: 8,
          padding: 3,
        }}
      >
        {/* eslint-disable-next-line @next/next/no-img-element */}
        <img src={logoMarkSrc} width="100%" height="100%" style={{ objectFit: "contain" }} alt="" />
      </div>
    ),
    size,
  );
}
