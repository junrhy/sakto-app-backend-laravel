import { useState } from 'react';
import { Head } from '@inertiajs/react';
import { router } from '@inertiajs/core';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
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
}

interface ClientDetail {
    id: number;
    client_id: number;
    app_name: string;
    name: string;
    value: string;
    client_identifier: string;
}

interface Props {
    clientDetail: ClientDetail;
    clients: Client[];
}

interface FormData {
    [key: string]: string;
    client_id: string;
    app_name: string;
    name: string;
    value: string;
    client_identifier: string;
}

export default function Edit({ clientDetail, clients }: Props) {
    const [form, setForm] = useState<FormData>({
        client_id: clientDetail.client_id.toString(),
        app_name: clientDetail.app_name,
        name: clientDetail.name,
        value: clientDetail.value,
        client_identifier: clientDetail.client_identifier,
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        
        router.put(route('clientdetails.update', clientDetail.id), form, {
            onSuccess: () => {
                toast.success('Client detail updated successfully');
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
            header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">Edit Client Detail</h2>}
        >
            <Head title="Edit Client Detail" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <div className="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6">
                            <div className="mb-6">
                                <h2 className="text-2xl font-semibold">Edit Client Detail</h2>
                            </div>

                            <form onSubmit={handleSubmit} className="space-y-6 max-w-2xl">
                                <div className="space-y-2">
                                    <label htmlFor="client_id" className="block text-sm font-medium">
                                        Client
                                    </label>
                                    <Select 
                                        value={form.client_id}
                                        onValueChange={(value: string) => handleChange('client_id', value)}
                                    >
                                        <SelectTrigger>
                                            <SelectValue placeholder="Select a client" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {clients.map((client) => (
                                                <SelectItem 
                                                    key={client.id} 
                                                    value={client.id.toString()}
                                                >
                                                    {client.name}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>

                                <div className="space-y-2">
                                    <label htmlFor="app_name" className="block text-sm font-medium">
                                        App Name
                                    </label>
                                    <Input
                                        id="app_name"
                                        value={form.app_name}
                                        onChange={(e: React.ChangeEvent<HTMLInputElement>) => 
                                            handleChange('app_name', e.target.value)
                                        }
                                        required
                                    />
                                </div>

                                <div className="space-y-2">
                                    <label htmlFor="name" className="block text-sm font-medium">
                                        Name
                                    </label>
                                    <Input
                                        id="name"
                                        value={form.name}
                                        onChange={(e: React.ChangeEvent<HTMLInputElement>) => 
                                            handleChange('name', e.target.value)
                                        }
                                        required
                                    />
                                </div>

                                <div className="space-y-2">
                                    <label htmlFor="value" className="block text-sm font-medium">
                                        Value
                                    </label>
                                    <Input
                                        id="value"
                                        value={form.value}
                                        onChange={(e: React.ChangeEvent<HTMLInputElement>) => 
                                            handleChange('value', e.target.value)
                                        }
                                        required
                                    />
                                </div>

                                <div className="space-y-2">
                                    <label htmlFor="client_identifier" className="block text-sm font-medium">
                                        Client Identifier
                                    </label>
                                    <Input
                                        id="client_identifier"
                                        value={form.client_identifier}
                                        onChange={(e: React.ChangeEvent<HTMLInputElement>) => 
                                            handleChange('client_identifier', e.target.value)
                                        }
                                        required
                                    />
                                </div>

                                <div className="flex gap-4">
                                    <Button type="submit">
                                        Update Client Detail
                                    </Button>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        onClick={() => router.visit(route('clientdetails.index'))}
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