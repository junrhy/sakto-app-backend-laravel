import { Input } from '@/Components/ui/input';
import { cn } from '@/lib/utils';
import * as React from 'react';

export function CalendarDateRangePicker({
    className,
}: React.HTMLAttributes<HTMLDivElement>) {
    const [date, setDate] = React.useState<string>('');

    return (
        <div className={cn('flex items-center gap-2', className)}>
            <Input
                type="date"
                value={date}
                onChange={(e: React.ChangeEvent<HTMLInputElement>) =>
                    setDate(e.target.value)
                }
                className="w-[200px]"
            />
        </div>
    );
}
