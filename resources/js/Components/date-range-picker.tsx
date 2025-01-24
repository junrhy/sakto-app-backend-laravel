import * as React from "react"
import { CalendarIcon } from "@heroicons/react/24/outline"
import { format } from "date-fns"
import { cn } from "@/lib/utils"
import { Button } from "@/Components/ui/button"
import {
  Popover,
  PopoverContent,
  PopoverTrigger,
} from "@/Components/ui/popover"
import { Input } from "@/Components/ui/input"

export function CalendarDateRangePicker({
  className,
}: React.HTMLAttributes<HTMLDivElement>) {
  const [date, setDate] = React.useState<string>("")

  return (
    <div className={cn("flex items-center gap-2", className)}>
      <Input
        type="date"
        value={date}
        onChange={(e: React.ChangeEvent<HTMLInputElement>) => setDate(e.target.value)}
        className="w-[200px]"
      />
    </div>
  )
} 