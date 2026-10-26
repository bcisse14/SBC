import { revalidateTag } from "next/cache";
import { NextRequest, NextResponse } from "next/server";

export async function POST(request: NextRequest) {
  const auth = request.headers.get("x-revalidate-secret");
  if (!auth || auth !== process.env.REVALIDATE_SECRET) {
    return NextResponse.json({ ok: false }, { status: 401 });
  }

  revalidateTag("site-content", "max");

  return NextResponse.json({ ok: true });
}
