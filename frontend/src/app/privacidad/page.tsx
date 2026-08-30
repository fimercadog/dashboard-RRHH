import type { Metadata } from "next";
import { LegalPage } from "@/components/marketing/legal-page";

export const metadata: Metadata = {
  title: "Politica de Tratamiento de Datos Personales",
  description:
    "Como DFC Talento Humano recolecta, usa y protege los datos personales, y como ejercer tus derechos conforme a la Ley 1581 de 2012.",
};

export default function PrivacyPage() {
  return (
    <LegalPage
      title="Politica de Tratamiento de Datos Personales"
      updated="30 de agosto de 2026"
      intro="Esta politica describe el tratamiento que DFC Talento Humano da a los datos personales que recibe a traves de este sitio web, en cumplimiento de la Ley Estatutaria 1581 de 2012, el Decreto 1074 de 2015 y demas normas concordantes de la Republica de Colombia."
      sections={[
        {
          heading: "1. Responsable del tratamiento",
          body: [
            "Responsable: [Razon social], identificada con NIT [NIT], con domicilio en [direccion], [ciudad], Colombia.",
            "Canal de atencion para el ejercicio de derechos: [correo de contacto]. Telefono / WhatsApp: [telefono].",
          ],
        },
        {
          heading: "2. Datos que recolectamos",
          body: [
            "A traves de los formularios de contacto y de solicitud de demostracion recolectamos: nombre, empresa, correo electronico, numero de telefono o WhatsApp, numero aproximado de empleados, modulo de interes y el contenido del mensaje que nos envies.",
            "No solicitamos datos sensibles a traves de este sitio. Si decides incluirlos en un campo de texto libre, se entendera que autorizas su tratamiento para los fines aqui descritos.",
          ],
        },
        {
          heading: "3. Finalidades del tratamiento",
          body: [
            "Los datos se tratan para: (i) responder tus solicitudes de informacion; (ii) contactarte con fines comerciales sobre la plataforma y sus modulos; (iii) agendar y realizar demostraciones del producto; (iv) enviarte informacion sobre novedades, precios y condiciones del servicio; y (v) atender obligaciones legales y requerimientos de autoridades competentes.",
          ],
        },
        {
          heading: "4. Autorizacion",
          body: [
            "Al marcar la casilla de autorizacion en el formulario y enviarlo, otorgas tu consentimiento previo, expreso e informado para el tratamiento de tus datos personales conforme a esta politica.",
          ],
        },
        {
          heading: "5. Derechos del titular",
          body: [
            "Como titular de los datos tienes derecho a: conocer, actualizar y rectificar tus datos; solicitar prueba de la autorizacion otorgada; ser informado sobre el uso que se ha dado a tus datos; presentar quejas ante la Superintendencia de Industria y Comercio; revocar la autorizacion y/o solicitar la supresion de los datos cuando no exista un deber legal o contractual de conservarlos; y acceder de forma gratuita a tus datos.",
          ],
        },
        {
          heading: "6. Procedimiento para consultas y reclamos",
          body: [
            "Puedes ejercer tus derechos escribiendo a [correo de contacto], indicando tu nombre, el derecho que deseas ejercer y una descripcion de tu solicitud.",
            "Las consultas se atienden en un plazo maximo de diez (10) dias habiles. Los reclamos se atienden en un plazo maximo de quince (15) dias habiles, contados a partir del dia siguiente a su recepcion; si no es posible atenderlos dentro de ese plazo, se te informara sobre los motivos de la demora y la nueva fecha de respuesta, que no superara los ocho (8) dias habiles adicionales.",
          ],
        },
        {
          heading: "7. Conservacion y seguridad",
          body: [
            "Los datos se conservan mientras exista una relacion comercial activa o un interes legitimo de contacto, y por el tiempo adicional que exijan las obligaciones legales aplicables.",
            "Adoptamos medidas tecnicas, humanas y administrativas razonables para proteger los datos contra acceso no autorizado, perdida, alteracion o uso fraudulento.",
          ],
        },
        {
          heading: "8. Vigencia",
          body: [
            "Esta politica rige a partir de su publicacion. Cualquier cambio sustancial sera informado a traves de este sitio web antes de su entrada en vigor.",
          ],
        },
        {
          heading: "Nota",
          body: [
            "Este documento es una plantilla base y debe ser revisada y completada (datos entre corchetes) por un abogado antes de su uso comercial definitivo.",
          ],
        },
      ]}
    />
  );
}
