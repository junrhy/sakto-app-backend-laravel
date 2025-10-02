import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/Components/ui/select';
import { Textarea } from '@/Components/ui/textarea';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { router } from '@inertiajs/core';
import { Head } from '@inertiajs/react';
import { useState } from 'react';
import { toast } from 'sonner';

interface Message {
    id: number;
    client_identifier: string;
    subject: string;
    message: string;
    type: string;
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

export default function Edit({ message }: Props) {
    const [form, setForm] = useState({
        subject: message.subject,
        message: message.message,
        type: message.type,
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        router.put(route('inbox.update', message.id), form, {
            onSuccess: () => {
                toast.success('Message updated successfully');
            },
            onError: (errors) => {
                Object.keys(errors).forEach((key) => {
                    toast.error(errors[key]);
                });
            },
        });
    };

    const handleChange = (field: string, value: string) => {
        setForm((prev) => ({
            ...prev,
            [field]: value,
        }));
    };

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Edit Message
                </h2>
            }
        >
            <Head title="Edit Message" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <div className="overflow-hidden bg-white shadow-sm dark:bg-gray-800 sm:rounded-lg">
                        <div className="p-6">
                            <div className="mb-6">
                                <h2 className="text-2xl font-semibold">
                                    Edit Message
                                </h2>
                            </div>

                            <form
                                onSubmit={handleSubmit}
                                className="max-w-2xl space-y-6"
                            >
                                <div className="space-y-2">
                                    <label className="block text-sm font-medium">
                                        Client Name
                                    </label>
                                    <Input
                                        value={
                                            message.client
                                                ? message.client.name
                                                : 'Client not found'
                                        }
                                        disabled
                                        className="bg-gray-100"
                                    />
                                </div>

                                <div className="space-y-2">
                                    <label
                                        htmlFor="subject"
                                        className="block text-sm font-medium"
                                    >
                                        Subject
                                    </label>
                                    <Input
                                        id="subject"
                                        value={form.subject}
                                        onChange={(e) =>
                                            handleChange(
                                                'subject',
                                                e.target.value,
                                            )
                                        }
                                        required
                                    />
                                </div>

                                <div className="space-y-2">
                                    <label
                                        htmlFor="type"
                                        className="block text-sm font-medium"
                                    >
                                        Message Type
                                    </label>
                                    <Select
                                        value={form.type}
                                        onValueChange={(value) =>
                                            handleChange('type', value)
                                        }
                                    >
                                        <SelectTrigger>
                                            <SelectValue placeholder="Select message type" />
                                        </SelectTrigger>
                                        <SelectContent>
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
                                </div>

                                <div className="space-y-2">
                                    <label
                                        htmlFor="message"
                                        className="block text-sm font-medium"
                                    >
                                        Message
                                    </label>
                                    <Textarea
                                        id="message"
                                        value={form.message}
                                        onChange={(e) =>
                                            handleChange(
                                                'message',
                                                e.target.value,
                                            )
                                        }
                                        rows={6}
                                        required
                                    />
                                </div>

                                <div className="flex gap-4">
                                    <Button type="submit">
                                        Update Message
                                    </Button>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        onClick={() =>
                                            router.visit(route('inbox.index'))
                                        }
                                    >
                                        Cancel
                                    </Button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
