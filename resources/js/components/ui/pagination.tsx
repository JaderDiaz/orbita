import { Link } from "@inertiajs/react"
import { ChevronLeft, ChevronRight, MoreHorizontal } from "lucide-react"
import * as React from "react"

import { cn } from "@/lib/utils"

function Pagination({ className, ...props }: React.ComponentProps<"nav">) {
  return (
    <nav
      role="navigation"
      aria-label="Paginación"
      data-slot="pagination"
      className={cn("flex w-full justify-center", className)}
      {...props}
    />
  )
}

function PaginationContent({ className, ...props }: React.ComponentProps<"ul">) {
  return (
    <ul
      data-slot="pagination-content"
      className={cn("flex flex-row items-center gap-1", className)}
      {...props}
    />
  )
}

function PaginationItem(props: React.ComponentProps<"li">) {
  return <li data-slot="pagination-item" {...props} />
}

/**
 * A page link. `href` is null for the page you are already on and for the
 * arrows at either end, so the same component renders both states.
 */
function PaginationLink({
  className,
  isActive,
  href,
  children,
  label,
}: {
  className?: string
  isActive?: boolean
  href: string | null
  children: React.ReactNode
  label?: string
}) {
  const shared = cn(
    "flex h-8 min-w-8 items-center justify-center rounded-md px-2 text-[12.5px] transition-colors",
    isActive
      ? "bg-foreground text-background font-semibold"
      : "text-text-secondary hover:bg-surface-subtle",
    className
  )

  if (href === null) {
    return (
      <span
        aria-disabled
        aria-label={label}
        className={cn(shared, "pointer-events-none opacity-35")}
      >
        {children}
      </span>
    )
  }

  return (
    <Link
      href={href}
      aria-label={label}
      aria-current={isActive ? "page" : undefined}
      preserveScroll
      preserveState
      className={shared}
    >
      {children}
    </Link>
  )
}

function PaginationPrevious({ href }: { href: string | null }) {
  return (
    <PaginationLink href={href} label="Página anterior">
      <ChevronLeft className="size-3.5" />
    </PaginationLink>
  )
}

function PaginationNext({ href }: { href: string | null }) {
  return (
    <PaginationLink href={href} label="Página siguiente">
      <ChevronRight className="size-3.5" />
    </PaginationLink>
  )
}

function PaginationEllipsis({ className }: { className?: string }) {
  return (
    <span
      aria-hidden
      data-slot="pagination-ellipsis"
      className={cn("text-text-tertiary flex h-8 w-8 items-center justify-center", className)}
    >
      <MoreHorizontal className="size-3.5" />
    </span>
  )
}

export {
  Pagination,
  PaginationContent,
  PaginationEllipsis,
  PaginationItem,
  PaginationLink,
  PaginationNext,
  PaginationPrevious,
}
