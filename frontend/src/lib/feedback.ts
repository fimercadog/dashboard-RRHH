// Formulario de comentarios (Google Forms, compartido entre desarrollos FidelOS).
const FORM_URL =
  "https://docs.google.com/forms/d/e/1FAIpQLSeqvt1vfLT58IEtY87LuDVv2forZnFUM02tx4ZjRGwfbchmLw/viewform";

// Nombre de este desarrollo, se prellena en el form para saber de donde viene
// el comentario.
const NOMBRE_DESARROLLO = "DFC Talento Humano";

// entry.NNNN de la pregunta "Desarrollo de origen" en el form.
const ENTRY_DESARROLLO = "entry.1663960621";

export function feedbackFormUrl(): string {
  if (!ENTRY_DESARROLLO) return FORM_URL;
  return `${FORM_URL}?usp=pp_url&${ENTRY_DESARROLLO}=${encodeURIComponent(NOMBRE_DESARROLLO)}`;
}

export function openFeedbackForm(): void {
  window.open(feedbackFormUrl(), "_blank", "noopener,noreferrer");
}
