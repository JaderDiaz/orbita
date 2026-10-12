import * as React from "react"

import { cn } from "@/lib/utils"

/**
 * The prototype's control: 10px radius, 10/12 padding, 13.5px, a hairline
 * shadow, a darker border on hover and the brand focus ring. Every input in the
 * application uses this shape, so it lives here rather than per screen.
 */
function Input({ className, type, ...props }: React.ComponentProps<"input">) {
  return (
    <input
      type={type}
      data-slot="input"
      className={cn(
        "border-input file:text-foreground placeholder:text-text-tertiary selection:bg-primary selection:text-primary-foreground shadow-control flex w-full min-w-0 rounded-lg border bg-card px-3 py-2.5 text-[13.5px] transition-[border-color,box-shadow] outline-none file:inline-flex file:h-7 file:border-0 file:bg-transparent file:text-sm file:font-medium disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50",
        "hover:border-[rgba(0,0,0,.24)]",
        "focus-visible:focus-ring-brand",
        "aria-invalid:border-destructive aria-invalid:ring-destructive/20",
        className
      )}
      {...props}
    />
  )
}

export { Input }
