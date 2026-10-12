import * as React from "react"

import { cn } from "@/lib/utils"

/**
 * The prototype's select: the same control as an input, with its own orange
 * chevron drawn as a background image so the native arrow never shows.
 *
 * A native select rather than the Radix one because the prototype's is native,
 * and because a short, static list of catalog options gains nothing from a
 * custom popover while losing the platform's keyboard and mobile behaviour.
 */
const CHEVRON =
  "url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='20' height='20' viewBox='0 0 20 20'%3E%3Crect width='20' height='20' rx='5.5' fill='%23f97316'/%3E%3Cpath d='M7 8.2 10 5.6l3 2.6M7 11.8l3 2.6 3-2.6' fill='none' stroke='%23fff' stroke-width='1.6' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E\")"

function NativeSelect({
  className,
  children,
  ...props
}: React.ComponentProps<"select">) {
  return (
    <select
      data-slot="native-select"
      style={{
        backgroundImage: CHEVRON,
        backgroundRepeat: "no-repeat",
        backgroundPosition: "right 8px center",
        backgroundSize: "20px 20px",
      }}
      className={cn(
        "border-input shadow-control w-full min-w-0 cursor-pointer appearance-none rounded-lg border bg-card py-2.5 pr-10 pl-3 text-[13.5px] font-medium transition-[border-color,box-shadow] outline-none",
        "hover:border-[rgba(0,0,0,.24)]",
        "focus-visible:focus-ring-brand",
        "aria-invalid:border-destructive",
        className
      )}
      {...props}
    >
      {children}
    </select>
  )
}

export { NativeSelect }
