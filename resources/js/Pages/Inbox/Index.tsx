import { Button } from '@/Components/ui/button';
import { Checkbox } from '@/Components/ui/checkbox';
import { Input } from '@/Components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/Components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/Components/ui/table';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { router } from '@inertiajs/core';
import { Head } from '@inertiajs/react';
import { format } from 'date-fns';
import { useState } from 'react';
import { toast } from 'sonner';

interface Message {
    id: number;
    client_identifier: string;
    client_name?: string;
    subject: string;
    message: string;
    type: string;
    is_read: boolean;
    created_at: string;
}

interface Props {
    messages: {
        data: Message[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
}

export default function Index({ messages }: Props) {
    const [selectedMessages, setSelectedMessages] = useState<number[]>([]);
    const [searchTerm, setSearchTerm] = useState('');
    const [messageType, setMessageType] = useState('all');

    const handleSearch = (value: string) => {
        setSearchTerm(value);
        router.get(
            route('inbox.index'),
            {
                search: value,
                type: messageType !== 'all' ? messageType : undefined,
            },
            { preserveState: true },
        );
    };

    const handleTypeFilter = (value: string) => {
        setMessageType(value);
        router.get(
            route('inbox.index'),
            { search: searchTerm, type: value !== 'all' ? value : undefined },
            { preserveState: true },
        );
    };

    const handleDelete = (id: number) => {
        if (confirm('Are you sure you want to delete this message?')) {
            router.delete(route('inbox.destroy', id), {
                onSuccess: () => {
                    toast.success('Message deleted successfully');
                },
            });
        }
    };

    const handleBulkDelete = () => {
        if (selectedMessages.length === 0) {
            toast.error('Please select messages to delete');
            return;
        }

        if (confirm('Are you sure you want to delete the selected messages?')) {
            router.post(
                route('inbox.bulk-destroy'),
                { ids: selectedMessages },
                {
                    onSuccess: () => {
                        setSelectedMessages([]);
                        toast.success('Messages deleted successfully');
                    },
                },
            );
        }
    };

    const toggleSelectAll = () => {
        if (selectedMessages.length === messages.data.length) {
            setSelectedMessages([]);
        } else {
            setSelectedMessages(messages.data.map((message) => message.id));
        }
    };

    const toggleSelect = (id: number) => {
        if (selectedMessages.includes(id)) {
            setSelectedMessages(
                selectedMessages.filter((messageId) => messageId !== id),
            );
        } else {
            setSelectedMessages([...selectedMessages, id]);
        }
    };

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Manage Inbox Messages
                </h2>
            }
        >
            <Head title="Inbox Admin" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <div className="overflow-hidden bg-white shadow-sm dark:bg-gray-800 sm:rounded-lg">
                        <div className="p-6">
                            <div className="mb-6 flex items-center justify-between">
                                <h2 className="text-2xl font-semibold">
                                    Messages
                                </h2>
                                <Button
                                    onClick={() =>
                                        router.visit(route('inbox.create'))
                                    }
                                >
                                    New Message
                                </Button>
                            </div>

                            <div className="mb-6 flex gap-4">
                                <Input
                                    placeholder="Search messages..."
                                    value={searchTerm}
                                    onChange={(e) =>
                                        handleSearch(e.target.value)
                                    }
                                    className="max-w-sm"
                                />
                                <Select
                                    value={messageType}
                                    onValueChange={handleTypeFilter}
                                >
                                    <SelectTrigger className="w-[180px]">
                                        <SelectValue placeholder="Filter by type" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">
                                            All Types
                                        </SelectItem>
                                        <SelectItem value="notification">
                                            Notification
                                        </SelectItem>
                                        <SelectItem value="alert">
                                            Alert
                                        </SelectItem>
                                        <SelectItem value="message">
                                            Message
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                {selectedMessages.length > 0 && (
                                    <Button
                                        variant="destructive"
                                        onClick={handleBulkDelete}
                                    >
                                        Delete Selected
                                    </Button>
                                )}
                            </div>

                            <div className="rounded-md border">
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead className="w-[50px]">
                                                <Checkbox
                                                    checked={
                                                        selectedMessages.length ===
                                                        messages.data.length
                                                    }
                                                    onCheckedChange={
                                                        toggleSelectAll
                                                    }
                                                />
                                            </TableHead>
                                            <TableHead>Client Name</TableHead>
                                            <TableHead>Subject</TableHead>
                                            <TableHead>Type</TableHead>
                                            <TableHead>Date</TableHead>
                                            <TableHead>Actions</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {messages.data.map((message) => (
                                            <TableRow key={message.id}>
                                                <TableCell>
                                                    <Checkbox
                                                        checked={selectedMessages.includes(
                                                            message.id,
                                                        )}
                                                        onCheckedChange={() =>
                                                            toggleSelect(
                                                                message.id,
                                                            )
                                                        }
                                                    />
                                                </TableCell>
                                                <TableCell>
                                                    {message.client_name ||
                                                        message.client_identifier}
                                                </TableCell>
                                                <TableCell>
                                                    {message.subject}
                                                </TableCell>
                                                <TableCell>
                                                    <span
                                                        className={`rounded-full px-2 py-1 text-sm capitalize ${
                                                            message.type ===
                                                            'alert'
                                                                ? 'bg-red-100 text-red-800'
                                                                : message.type ===
                                                                    'notification'
                                                                  ? 'bg-blue-100 text-blue-800'
                                                                  : 'bg-gray-100 text-gray-800'
                                                        }`}
                                                    >
                                                        {message.type}
                                                    </span>
                                                </TableCell>
                                                <TableCell>
                                                    {format(
                                                        new Date(
                                                            message.created_at,
                                                        ),
                                                        'MMM d, yyyy',
                                                    )}
                                                </TableCell>
                                                <TableCell>
                                                    <div className="flex gap-2">
                                                        <Button
                                                            variant="outline"
                                                            size="sm"
                                                            onClick={() =>
                                                                router.visit(
                                                                    route(
                                                                        'inbox.show',
                                                                        message.id,
                                                                    ),
                                                                )
                                                            }
                                                        >
                                                            View
                                                        </Button>
                                                        <Button
                                                            variant="outline"
                                                            size="sm"
                                                            onClick={() =>
                                                                router.visit(
                                                                    route(
                                                                        'inbox.edit',
                                                                        message.id,
                                                                    ),
                                                                )
                                                            }
                                                        >
                                                            Edit
                                                        </Button>
                                                        <Button
                                                            variant="destructive"
                                                            size="sm"
                                                            onClick={() =>
                                                                handleDelete(
                                                                    message.id,
                                                                )
                                                            }
                                                        >
                                                            Delete
                                                        </Button>
                                                    </div>
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
