"use client";

import * as React from "react";

// ponytail: el Event('input') disparado programáticamente no actualiza react-hook-form setValue; si este hook se usa en forms RHF futuros, conectar via register ref callbacks

// Local type stubs for Web Speech API (not always present in tsconfig lib)
type SpeechRecognitionInstance = {
  lang: string;
  continuous: boolean;
  interimResults: boolean;
  onresult: ((event: SpeechRecognitionResultEvent) => void) | null;
  onerror: ((event: SpeechRecognitionErrorDetail) => void) | null;
  onend: (() => void) | null;
  start: () => void;
  stop: () => void;
};

type SpeechRecognitionResultEvent = {
  results: { 0: { transcript: string }; isFinal: boolean }[];
};

type SpeechRecognitionErrorDetail = {
  error: string;
};

type SpeechInputHook = {
  supported: boolean;
  listening: boolean;
  start: (ref: React.RefObject<HTMLInputElement | HTMLTextAreaElement | null>) => void;
  stop: () => void;
};

function getSpeechRecognitionCtor(): (new () => SpeechRecognitionInstance) | null {
  if (typeof window === "undefined") return null;
  const w = window as unknown as Record<string, unknown>;
  return (w["SpeechRecognition"] ?? w["webkitSpeechRecognition"] ?? null) as (new () => SpeechRecognitionInstance) | null;
}

export function useSpeechInput(): SpeechInputHook {
  const supported = typeof window !== "undefined" && getSpeechRecognitionCtor() !== null;

  const [listening, setListening] = React.useState(false);
  const recognitionRef = React.useRef<SpeechRecognitionInstance | null>(null);

  function stop() {
    if (recognitionRef.current) {
      recognitionRef.current.stop();
      recognitionRef.current = null;
    }
    setListening(false);
  }

  function startWithLang(
    ref: React.RefObject<HTMLInputElement | HTMLTextAreaElement | null>,
    lang: string,
    isRetry: boolean,
  ) {
    const Ctor = getSpeechRecognitionCtor();
    if (!Ctor) return;

    const recognition = new Ctor();
    recognition.lang = lang;
    recognition.continuous = false;
    recognition.interimResults = false;
    recognitionRef.current = recognition;

    recognition.onresult = (event) => {
      const result = event.results[0];
      if (result?.isFinal && ref.current) {
        const transcript = result[0].transcript;
        const prev = ref.current.value;
        ref.current.value = prev ? `${prev} ${transcript}` : transcript;
        ref.current.dispatchEvent(new Event("input", { bubbles: true }));
      }
    };

    recognition.onerror = (event) => {
      if (event.error === "language-not-supported" && !isRetry) {
        recognition.onend = null; // evitar que onend del primer recognition pise el estado del retry
        startWithLang(ref, "es-ES", true);
      } else {
        setListening(false);
      }
    };

    recognition.onend = () => {
      setListening(false);
    };

    recognition.start();
    setListening(true);
  }

  function start(ref: React.RefObject<HTMLInputElement | HTMLTextAreaElement | null>) {
    if (!supported) return;
    stop();
    startWithLang(ref, "es-CO", false);
  }

  React.useEffect(() => {
    return () => {
      if (recognitionRef.current) {
        recognitionRef.current.stop();
        recognitionRef.current = null;
      }
    };
  }, []);

  return { supported, listening, start, stop };
}
