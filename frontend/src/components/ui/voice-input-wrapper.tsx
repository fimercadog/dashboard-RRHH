"use client";

import * as React from "react";
import { Mic, MicOff } from "lucide-react";
import { useSpeechInput } from "@/hooks/useSpeechInput";

type VoiceInputWrapperProps = {
  children: React.ReactElement<{ className?: string; ref?: React.Ref<HTMLInputElement | HTMLTextAreaElement> }>;
  fieldRef: React.RefObject<HTMLInputElement | HTMLTextAreaElement | null>;
  voice?: boolean;
};

export function VoiceInputWrapper({ children, fieldRef, voice }: VoiceInputWrapperProps) {
  const { supported, listening, start, stop } = useSpeechInput();

  React.useEffect(() => {
    return () => {
      stop();
    };
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  if (!voice || !supported) {
    return children;
  }

  const cloned = React.cloneElement(children, {
    className: [children.props.className ?? "", "pr-10"].filter(Boolean).join(" "),
    ref: fieldRef,
  });

  return (
    <div className="relative">
      {cloned}
      <button
        type="button"
        aria-label={listening ? "Detener dictado" : "Activar dictado"}
        onClick={() => (listening ? stop() : start(fieldRef))}
        className="absolute right-2 top-1/2 -translate-y-1/2 h-7 w-7 flex items-center justify-center rounded hover:bg-muted transition-colors"
      >
        {listening ? (
          <MicOff className="h-4 w-4 text-primary animate-pulse" />
        ) : (
          <Mic className="h-4 w-4 text-muted-foreground" />
        )}
      </button>
    </div>
  );
}
