import { ImageResponse } from "next/og";

export const size = { width: 32, height: 32 };
export const contentType = "image/png";

export default function Icon() {
  return new ImageResponse(
    (
      <div
        style={{
          width: "100%",
          height: "100%",
          display: "flex",
          alignItems: "center",
          justifyContent: "center",
          background: "#a3175a",
          borderRadius: 8,
          color: "#ffffff",
          fontSize: 22,
          fontWeight: 700,
          fontFamily: "sans-serif",
        }}
      >
        D
      </div>
    ),
    size,
  );
}
