import { useState } from 'react';
import { Head } from '@inertiajs/react';
import { router } from '@inertiajs/core';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Textarea } from '@/Components/ui/textarea';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/Components/ui/select';
import { toast } from 'sonner';

interface Client {
    id: number;
    name: string;
    client_identifier: string;
}

interface Props {
    clients: Client[];
}

interface FormData {
    [key: string]: string;
    client_identifier: string;
    subject: string;
    message: string;
    type: string;
}

export default function Create({ clients }: Props) {
    const [form, setForm] = useState<FormData>({
        client_identifier: '',
        subject: '',
        message: '',
        type: '',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        
        router.post(route('inbox.store'), form, {
            onSuccess: () => {
                toast.success('Message sent successfully');
            },
            onError: (errors: Record<string, string>) => {
                Object.keys(errors).forEach(key => {
                    toast.error(errors[key]);
                });
            },
        });
    };

    const handleChange = (field: keyof FormData, value: string) => {
        setForm(prev => ({
            ...prev,
            [field]: value
        }));
    };

    return (
        <AuthenticatedLayout
            header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">Create New Message</h2>}
        >
            <Head title="Create Message" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <div className="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6">
                            <div className="mb-6">
                                <h2 className="text-2xl font-semibold">Create New Message</h2>
                            </div>

                            <form onSubmit={handleSubmit} className="space-y-6 max-w-2xl">
                                <div className="space-y-2">
                                    <label htmlFor="client_identifier" className="block text-sm font-medium">
                                        Client
                                    </label>
                                    <Select 
                                        value={form.client_identifier}
                                        onValueChange={(value: string) => handleChange('client_identifier', value)}
                                    >
                                        <SelectTrigger>
                                            <SelectValue placeholder="Select a client" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {clients.map((client) => (
                                                <SelectItem 
                                                    key={client.id} 
                                                    value={client.client_identifier}
                                                >
                                                    {client.name}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>

                                <div className="space-y-2">
                                    <label htmlFor="subject" className="block text-sm font-medium">
                                        Subject
                                    </label>
                                    <Input
                                        id="subject"
                                        value={form.subject}
                                        onChange={(e: React.ChangeEvent<HTMLInputElement>) => 
                                            handleChange('subject', e.target.value)
                                        }
                                        required
                                    />
                                </div>

                                <div className="space-y-2">
                                    <label htmlFor="type" className="block text-sm font-medium">
                                        Message Type
                                    </label>
                                    <Select 
                                        value={form.type} 
                                        onValueChange={(value: string) => handleChange('type', value)}
                                    >
                                        <SelectTrigger>
                                            <SelectValue placeholder="Select message type" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="notification">Notification</SelectItem>
                                            <SelectItem value="alert">Alert</SelectItem>
                                            <SelectItem value="message">Message</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>

                                <div className="space-y-2">
                                    <label htmlFor="message" className="block text-sm font-medium">
                                        Message
                                    </label>
                                    <Textarea
                                        id="message"
                                        value={form.message}
                                        onChange={(e: React.ChangeEvent<HTMLTextAreaElement>) => 
                                            handleChange('message', e.target.value)
                                        }
                                        rows={6}
                                        required
                                    />
                                </div>

                                <div className="flex gap-4">
                                    <Button type="submit">
                                        Send Message
                                    </Button>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        onClick={() => router.visit(route('inbox.index'))}
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