export class AdminApiError extends Error {
  constructor(message: string, public readonly status: number) {
    super(message);
  }
}

export async function adminRequest<T>(path: string, options: RequestInit = {}, csrfToken?: string): Promise<T> {
  let response: Response;
  try {
    response = await fetch(path, {
      ...options,
      credentials: "include",
      headers: {
        ...(options.body instanceof FormData ? {} : { "Content-Type": "application/json" }),
        ...(csrfToken ? { "X-CSRF-TOKEN": csrfToken } : {}),
        ...options.headers,
      },
    });
  } catch {
    throw new AdminApiError("Connexion interrompue. Vos modifications ne sont pas enregistrées. Réessayez.", 0);
  }
  if ([401, 403].includes(response.status)) {
    throw new AdminApiError(
      path === "/api/admin/login" && response.status === 401
        ? "Connexion impossible. Vérifiez votre adresse e-mail et votre mot de passe."
        : "Votre session a expiré ou cet accès n’est pas autorisé. Reconnectez-vous.",
      response.status,
    );
  }
  const text = await response.text();
  let data: T & { message?: string };
  try {
    data = text ? JSON.parse(text) : {};
  } catch {
    throw new AdminApiError("Le serveur a renvoyé une réponse illisible. Réessayez.", response.status);
  }
  if (!response.ok) {
    throw new AdminApiError(
      data.message ?? `L’opération a échoué (code ${response.status}).`,
      response.status,
    );
  }
  return data;
}

export function errorMessage(error: unknown): string {
  return error instanceof Error ? error.message : "Une erreur inattendue est survenue.";
}

export function moveItem<T>(items: T[], index: number, destination: number): T[] {
  if (destination < 0 || destination >= items.length) return items;
  const result = [...items];
  const [item] = result.splice(index, 1);
  result.splice(destination, 0, item);
  return result;
}
