import { Button } from '@/Components/ui/button';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { router } from '@inertiajs/core';
import { Head } from '@inertiajs/react';
import { format } from 'date-fns';

interface Message {
    id: number;
    client_identifier: string;
    subject: string;
    message: string;
    type: string;
    is_read: boolean;
    created_at: string;
    read_at: string | null;
    client?: {
        id: number;
        name: string;
        email: string;
        client_identifier: string;
    } | null;
}

interface Props {
    message: Message;
}

export default function Show({ message }: Props) {
    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    View Message
                </h2>
            }
        >
            <Head title="View Message" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <div className="overflow-hidden bg-white shadow-sm dark:bg-gray-800 sm:rounded-lg">
                        <div className="p-6">
                            <div className="mb-6 flex items-center justify-between">
                                <h2 className="text-2xl font-semibold">
                                    Message Details
                                </h2>
                                <div className="flex gap-4">
                                    <Button
                                        variant="outline"
                                        onClick={() =>
                                            router.visit(
                                                route('inbox.edit', message.id),
                                            )
                                        }
                                    >
                                        Edit Message
                                    </Button>
                                    <Button
                                        variant="outline"
                                        onClick={() =>
                                            router.visit(route('inbox.index'))
                                        }
                                    >
                                        Back to List
                                    </Button>
                                </div>
                            </div>

                            <div className="max-w-3xl space-y-6">
                                <div className="grid grid-cols-2 gap-4">
                                    <div>
                                        <h3 className="text-sm font-medium text-gray-500 dark:text-gray-400">
                                            Client Name
                                        </h3>
                                        <p className="mt-1">
                                            {message.client
                                                ? message.client.name
                                                : 'Client not found'}
                                        </p>
                                    </div>

                                    <div>
                                        <h3 className="text-sm font-medium text-gray-500 dark:text-gray-400">
                                            Type
                                        </h3>
                                        <p className="mt-1">
                                            <span
                                                className={`rounded-full px-2 py-1 text-sm capitalize ${
                                                    message.type === 'alert'
                                                        ? 'bg-red-100 text-red-800'
                                                        : message.type ===
                                                            'notification'
                                                          ? 'bg-blue-100 text-blue-800'
                                                          : 'bg-gray-100 text-gray-800'
                                                }`}
                                            >
                                                {message.type}
                                            </span>
                                        </p>
                                    </div>

                                    <div>
                                        <h3 className="text-sm font-medium text-gray-500 dark:text-gray-400">
                                            Created At
                                        </h3>
                                        <p className="mt-1">
                                            {format(
                                                new Date(message.created_at),
                                                'PPpp',
                                            )}
                                        </p>
                                    </div>

                                    <div>
                                        <h3 className="text-sm font-medium text-gray-500 dark:text-gray-400">
                                            Read Status
                                        </h3>
                                        <p className="mt-1">
                                            {message.is_read ? (
                                                <span className="text-green-600">
                                                    Read on{' '}
                                                    {format(
                                                        new Date(
                                                            message.read_at!,
                                                        ),
                                                        'PPpp',
                                                    )}
                                                </span>
                                            ) : (
                                                <span className="text-yellow-600">
                                                    Unread
                                                </span>
                                            )}
                                        </p>
                                    </div>
                                </div>

                                <div>
                                    <h3 className="text-sm font-medium text-gray-500 dark:text-gray-400">
                                        Subject
                                    </h3>
                                    <p className="mt-1 text-lg font-medium">
                                        {message.subject}
                                    </p>
                                </div>

                                <div>
                                    <h3 className="text-sm font-medium text-gray-500 dark:text-gray-400">
                                        Message
                                    </h3>
                                    <div className="mt-2 rounded-lg bg-gray-50 p-4 dark:bg-gray-900">
                                        <p className="whitespace-pre-wrap">
                                            {message.message}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
