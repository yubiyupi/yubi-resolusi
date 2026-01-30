package main

import (
	"context"
	"errors"
	"grpc-course-protobuf/pb/chat"
	"grpc-course-protobuf/pb/common"
	"grpc-course-protobuf/pb/user"
	"io"
	"log"
	"net"
	"strings"
	"time"

	protovalidate "buf.build/go/protovalidate"
	"google.golang.org/grpc"
	"google.golang.org/grpc/codes"
	"google.golang.org/grpc/metadata"
	"google.golang.org/grpc/reflection"
	"google.golang.org/grpc/status"
	"google.golang.org/protobuf/types/known/timestamppb"
)

func loggingMiddleware(ctx context.Context, req any, info *grpc.UnaryServerInfo, handler grpc.UnaryHandler) (resp any, err error) {
	log.Println("Masuk logging middleware")
	log.Println(info.FullMethod)
	res, err := handler(ctx, req)

	log.Println("Setelah request")
	return res, err
}

func authMiddleware(ctx context.Context, req any, info *grpc.UnaryServerInfo, handler grpc.UnaryHandler) (resp any, err error) {

	log.Println("Masuk auth middleware")

	if info.FullMethod == "/user.UserService/Login" {
		return handler(ctx, req)
	}

	md, ok := metadata.FromIncomingContext(ctx)
	if !ok {
		return nil, status.Error(codes.Unknown, "failed parsing metadata")
	}

	authToken, ok := md["authorization"]
	if !ok {
		return nil, status.Error(codes.Unauthenticated, "token doesn't exist")
	}

	log.Println(authToken[0])

	splitToken := strings.Split(authToken[0], "")
	token := splitToken[1]

	if token != "secret" {
		return nil, status.Error(codes.Unauthenticated, "token is not valid")
	}

	return handler(ctx, req)
}

type userService struct {
	user.UnimplementedUserServiceServer
}

func (us *userService) Login(ctx context.Context, loginrequest *user.LoginRequest) (*user.LoginResponse, error) {
	return &user.LoginResponse{
		Base: &common.BaseResponse{
			StatusCode: 200,
			IsSuccess:  true,
			Message:    "Success",
		},
		AccessToken:  "secret",
		RefreshToken: "refresh_token",
	}, nil
}

func (us *userService) CreateUser(ctx context.Context, userRequest *user.User) (*user.CreateResponse, error) {
	if err := protovalidate.Validate(userRequest); err != nil {
		if ve, ok := err.(*protovalidate.ValidationError); ok {
			for _, fieldErr := range ve.Violations {
				log.Printf(
					"Field %s message %s",
					fieldErr.Proto.Field.Elements[0].FieldName,
					fieldErr.Proto.Message,
				)
			}
		}

		return nil, status.Errorf(codes.InvalidArgument, "validation error: %v", err)
	}

	log.Println("User is created")
	return &user.CreateResponse{
		Base: &common.BaseResponse{
			StatusCode: 200,
			IsSuccess:  true,
			Message:    "User created",
		},
		CreatedAt: timestamppb.Now(),
	}, nil

}

type chatService struct {
	chat.UnimplementedChatServiceServer
}

func (cs *chatService) SendMessage(stream grpc.ClientStreamingServer[chat.ChatMessage, chat.ChatResponse]) error {

	for {
		req, err := stream.Recv()
		if err != nil {
			if errors.Is(err, io.EOF) {
				break
			}
			return status.Errorf(codes.Unknown, "Error receiving message %v", err)
		}

		log.Printf("Receive Message: %s, to %d", req.Content, req.UserId)
	}

	return stream.SendAndClose(&chat.ChatResponse{
		Message: "Thanks for the message!",
	})
}

func (cs *chatService) ReceiveMessage(req *chat.ReceiveMessageRequest, stream grpc.ServerStreamingServer[chat.ChatMessage]) error {
	log.Printf("Got connection request from %d\n ", req.UserId)

	for i := 0; i < 10; i++ {
		err := stream.Send(&chat.ChatMessage{
			UserId:  123,
			Content: "Hi",
		})
		if err != nil {
			return status.Errorf(codes.Unknown, "error sending message  to client %v", err)
		}
	}

	return nil
}

func (cs *chatService) Chat(stream grpc.BidiStreamingServer[chat.ChatMessage, chat.ChatMessage]) error {

	for {
		msg, err := stream.Recv()
		if err != nil {
			if errors.Is(err, io.EOF) {
				break
			}

			return status.Errorf(codes.Unknown, "error receiving message")
		}

		log.Printf("Got message from %d content %s", msg.UserId, msg.Content)

		time.Sleep(2 * time.Second)

		err = stream.Send(&chat.ChatMessage{
			UserId:  50,
			Content: "Reply from server",
		})
		if err != nil {
			return status.Errorf(codes.Unknown, "method chat not implemented")
		}

		err = stream.Send(&chat.ChatMessage{
			UserId:  50,
			Content: "Reply from server #2",
		})
		if err != nil {
			return status.Errorf(codes.Unknown, "method chat not implemented")
		}
	}

	return nil
}

func main() {
	lis, err := net.Listen("tcp", ":8082")
	if err != nil {
		log.Fatal("There is error in your net listen ", err)
	}

	serv := grpc.NewServer(
		grpc.ChainUnaryInterceptor(
			loggingMiddleware,
			authMiddleware,
		),
	)

	user.RegisterUserServiceServer(serv, &userService{})
	chat.RegisterChatServiceServer(serv, &chatService{})

	reflection.Register(serv)

	if err := serv.Serve(lis); err != nil {
		log.Fatal("Error running server ", err)
	}
}
