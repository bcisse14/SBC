import { NextRequest, NextResponse } from "next/server";

export function proxy(request: NextRequest) {
  const adminPath = process.env.ADMIN_PATH || "/atelier-admin";
  const { pathname } = request.nextUrl;

  if (pathname === "/private-atelier" || pathname.startsWith("/private-atelier/")) {
    return new NextResponse("Not found", { status: 404 });
  }

  if (pathname === adminPath || pathname.startsWith(`${adminPath}/`)) {
    const rewritten = pathname.replace(adminPath, "/private-atelier") || "/private-atelier";
    const url = request.nextUrl.clone();
    url.pathname = rewritten;

    const response = NextResponse.rewrite(url);
    response.headers.set("X-Robots-Tag", "noindex, nofollow");
    return response;
  }

  return NextResponse.next();
}

export const config = {
  matcher: ["/:path*"],
};
